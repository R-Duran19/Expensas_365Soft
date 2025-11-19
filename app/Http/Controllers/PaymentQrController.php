<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Qr;
use App\Models\Pago;
use App\Models\Propietario;
use App\Models\PropertyExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;

class PaymentQrController extends Controller
{
    private function generarToken()
    {
        $client = new Client();

        $url = 'https://sip.mc4.com.bo:8443/autenticacion/v1/generarToken';
        $headers = [
            'apikey' => '2977cb47ecc0fd3a326bd0c0cf57d04becaa59c2f101c3f7',
            'Content-Type' => 'application/json',
        ];
        $body = json_encode([
            'password' => '365Soft',
            'username' => 'dev365',
        ]);

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $body,
            ]);

            $data = json_decode($response->getBody(), true);

            return $data['objeto']['token'];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $errorData = json_decode($response->getBody(), true);
                throw new \Exception("Error $statusCode: " . json_encode($errorData));
            } else {
                throw new \Exception("Error de conexión: " . $e->getMessage());
            }
        }
    }

    /**
     * Generar QR Dinámico (versión simplificada)
     */
    public function generarQrDinamico(Request $request)
    {
        try {
            $request->validate([
                'monto' => 'required|numeric|min:0.01',
                'propietario_id' => 'required|exists:propietarios,id',
                'property_expense_id' => 'nullable|exists:property_expenses,id'
            ]);

            // Configurar zona horaria
            config(['app.timezone' => 'America/La_Paz']);

            $propietario = Propietario::findOrFail($request->propietario_id);

            // Generar alias único con fecha y hora actual
            $timestamp = now()->format('YmdHis');
            $random = rand(100, 999);
            $alias = "QR365{$propietario->id}T{$timestamp}{$random}";

            // Obtener token
            $token = $this->generarToken();

            $url = 'https://sip.mc4.com.bo:8443/api/v1/generaQr';
            $headers = [
                'apikeyServicio' => '939aa1fcf73a32a737d495a059104a9a60a707074bceef68',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ];

            // Crear descripción del pago
            $detalleGlosa = "Expensas";
            if ($request->property_expense_id) {
                $propertyExpense = PropertyExpense::find($request->property_expense_id);
                if ($propertyExpense && $propertyExpense->propiedad) {
                    $detalleGlosa .= " - {$propietario->nombre_completo} - {$propertyExpense->propiedad->codigo}";
                } else {
                    $detalleGlosa .= " - {$propietario->nombre_completo}";
                }
            } else {
                $detalleGlosa .= " - {$propietario->nombre_completo}";
            }

            $body = json_encode([
                'alias' => $alias,
                'callback' => '000',
                'detalleGlosa' => $detalleGlosa,
                'monto' => $request->monto,
                'moneda' => 'BOB',
                'fechaVencimiento' => Carbon::now()->addDay()->format('d/m/Y'),
                'tipoSolicitud' => 'API',
                'unicoUso' => true
            ]);

            // Primero generar QR con el banco
            $client = new Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 30
            ]);

            $responseData = json_decode($response->getBody(), true);

            // Si el QR se generó correctamente en el banco, guardarlo en la BD
            if ($responseData['codigo'] === '0000') {
                $qr = new Qr();
                $qr->alias = $alias;
                $qr->estado = Qr::ESTADO_PENDIENTE;
                $qr->pago_id = null;
                $qr->monto = $request->monto;
                $qr->propietario_id = $request->propietario_id;
                $qr->expensa_id = $request->property_expense_id;
                $qr->fecha_vencimiento = Carbon::now()->addDay();
                $qr->detalle_glosa = $detalleGlosa;
                $qr->imagen_qr = $responseData['objeto'] ?? null;
                $qr->save();

                return response()->json([
                    'success' => true,
                    'qr_id' => $qr->id,
                    'alias' => $qr->alias,
                    'estado' => $qr->estado,
                    'monto' => $qr->monto,
                    'data' => $responseData,
                    'message' => 'QR generado exitosamente'
                ], $response->getStatusCode());
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar QR: ' . ($responseData['mensaje'] ?? 'Error desconocido'),
                    'data' => $responseData
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar QR: ' . $e->getMessage()
            ], 500);
        }
    }

  
    /**
     * Verificar estado del QR
     */
    public function verificarEstadoQr(Request $request)
    {
        try {
            $request->validate([
                'qr_id' => 'required|exists:qr,id',
                'alias' => 'required_without:qr_id|string'
            ]);

            // Configurar zona horaria
            config(['app.timezone' => 'America/La_Paz']);

            // Obtener el QR
            if ($request->qr_id) {
                $qr = Qr::findOrFail($request->qr_id);
                $alias = $qr->alias;
            } else {
                $alias = $request->alias;
                $qr = Qr::where('alias', $alias)->first();
            }

            if (!$qr) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR no encontrado'
                ], 404);
            }

            // Obtener token
            $token = $this->generarToken();

            $url = 'https://sip.mc4.com.bo:8443/api/v1/estadoTransaccion';
            $headers = [
                'apikeyServicio' => '939aa1fcf73a32a737d495a059104a9a60a707074bceef68',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ];

            $body = json_encode([
                'alias' => $alias
            ]);

            $client = new Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $body,
                'timeout' => 15
            ]);

            $responseData = json_decode($response->getBody(), true);

            // Procesar respuesta
            if ($responseData['codigo'] == '0000') {
                $estadoActual = $responseData['objeto']['estadoActual'];
                $estadoAnterior = $qr->estado;

                // Actualizar el estado en la base de datos
                $qr->estado = $estadoActual;
                $qr->save();

                // Si el estado es PAGADO, procesar el pago
                if ($estadoActual == Qr::ESTADO_PAGADO && $estadoAnterior !== Qr::ESTADO_PAGADO) {
                    $resultadoProceso = $this->procesarPagoQr($qr, $responseData);

                    return response()->json([
                        'success' => true,
                        'qr' => $qr->fresh(),
                        'estado_transaccion' => $responseData,
                        'alias_verificado' => $alias,
                        'payment_processed' => $resultadoProceso,
                        'message' => '¡Pago detectado y procesado exitosamente!'
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'qr' => $qr->fresh(),
                'estado_transaccion' => $responseData,
                'alias_verificado' => $alias,
                'message' => 'Estado verificado: ' . $qr->estado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar pago cuando el QR es marcado como pagado
     */
    private function procesarPagoQr(Qr $qr, $responseData)
    {
        try {
            DB::beginTransaction();

            // Actualizar estado del QR a pagado
            $qr->estado = Qr::ESTADO_PAGADO;
            $qr->save();

            // NOTA: No actualizamos property_expenses aquí porque
            // el PaymentAllocationService se encargará de eso
            // cuando se cree el registro del pago Payment

            DB::commit();

            return [
                'payment_processed' => true,
                'qr_id' => $qr->id,
                'qr_monto' => $qr->monto,
                'qr_expensa_id' => $qr->expensa_id,
                'message' => 'QR marcado como pagado. La imputación de fondos se procesará cuando se registre el pago.'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

  
    /**
     * Callback para notificaciones del sistema QR
     */
    public function handleCallback(Request $request)
    {
        $validatedData = $request->validate([
            'alias' => 'required|string|max:50',
            'numeroOrdenOriginante' => 'nullable|string|max:30',
            'monto' => 'nullable|numeric',
            'idQr' => 'nullable|string|max:30',
            'moneda' => 'nullable|string|max:10',
            'fechaproceso' => 'nullable|date',
            'cuentaCliente' => 'nullable|string|max:50',
            'nombreCliente' => 'nullable|string|max:250',
            'documentoCliente' => 'nullable|string|max:50',
        ]);

        $alias = $validatedData['alias'];
        $qr = Qr::where('alias', $alias)->first();

        $codigoRespuesta = '1212';
        $mensajeRespuesta = 'Alias no encontrado en la base de datos';

        if ($qr) {
            if ($qr->estado == Qr::ESTADO_PENDIENTE) {
                $codigoRespuesta = '1212';
                $mensajeRespuesta = 'Alias encontrado y estado es PENDIENTE';
            } elseif ($qr->estado == Qr::ESTADO_PAGADO) {
                $codigoRespuesta = '0000';
                $mensajeRespuesta = 'Registro Exitoso';
            }
        }

        return response()->json([
            'codigo' => $codigoRespuesta,
            'mensaje' => $mensajeRespuesta
        ], 200);
    }

    /**
     * Cancelar QR
     */
    public function cancelarQr($qrId)
    {
        try {
            $qr = Qr::findOrFail($qrId);

            if ($qr->estado == Qr::ESTADO_PAGADO) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar un QR que ya ha sido pagado'
                ], 400);
            }

            $qr->estado = Qr::ESTADO_CANCELADO;
            $qr->save();

            return response()->json([
                'success' => true,
                'message' => 'QR cancelado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar QR: ' . $e->getMessage()
            ], 500);
        }
    }
}