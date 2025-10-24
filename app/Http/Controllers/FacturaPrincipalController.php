<?php

namespace App\Http\Controllers;

use App\Models\FacturaPrincipal;
use App\Models\PeriodoFacturacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class FacturaPrincipalController extends Controller
{
    public function create(PeriodoFacturacion $periodo)
    {
        if (!$periodo->estaAbierto()) {
            return redirect()
                ->route('periodos.show', $periodo)
                ->withErrors(['error' => 'El período está cerrado']);
        }

        // Verificar cuántas facturas ya tiene
        $comerciales = $periodo->facturasPrincipales()->comerciales()->count();
        $domiciliarias = $periodo->facturasPrincipales()->domiciliarias()->count();

        return Inertia::render('FacturasPrincipales/Create', [
            'periodo' => $periodo,
            'tiene_comercial' => $comerciales >= 1,
            'tiene_domiciliarias' => $domiciliarias >= 2
        ]);
    }

    public function store(Request $request, PeriodoFacturacion $periodo)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:comercial,domiciliario',
            'numero_medidor_empresa' => 'required|string|max:50',
            'importe_bs' => 'required|numeric|min:0',
            'consumo_m3' => 'required|integer|min:0',
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_emision',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        // Validar límites
        $count = $periodo->facturasPrincipales()
            ->where('tipo', $validated['tipo'])
            ->count();

        if ($validated['tipo'] === 'comercial' && $count >= 1) {
            throw ValidationException::withMessages([
                'tipo' => 'Ya existe una factura comercial para este período'
            ]);
        }

        if ($validated['tipo'] === 'domiciliario' && $count >= 2) {
            throw ValidationException::withMessages([
                'tipo' => 'Ya existen 2 facturas domiciliarias para este período'
            ]);
        }

        $factura = FacturaPrincipal::create([
            ...$validated,
            'periodo_facturacion_id' => $periodo->id,
            'usuario_registro_id' => auth()->id()
        ]);

        return redirect()
            ->route('periodos.show', $periodo)
            ->with('success', 'Factura registrada exitosamente. Factor calculado automáticamente.');
    }

    public function edit(FacturaPrincipal $factura)
    {
        if (!$factura->periodoFacturacion->estaAbierto()) {
            return back()->withErrors([
                'error' => 'No se puede editar. El período está cerrado.'
            ]);
        }

        return Inertia::render('FacturasPrincipales/Edit', [
            'factura' => $factura->load('periodoFacturacion')
        ]);
    }

    public function update(Request $request, FacturaPrincipal $factura)
    {
        if (!$factura->periodoFacturacion->estaAbierto()) {
            return back()->withErrors([
                'error' => 'No se puede editar. El período está cerrado.'
            ]);
        }

        $validated = $request->validate([
            'numero_medidor_empresa' => 'required|string|max:50',
            'importe_bs' => 'required|numeric|min:0',
            'consumo_m3' => 'required|integer|min:0',
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_emision',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        $factura->update($validated);

        return redirect()
            ->route('periodos.show', $factura->periodo_facturacion_id)
            ->with('success', 'Factura actualizada. Factores recalculados.');
    }

    public function destroy(FacturaPrincipal $factura)
    {
        if (!$factura->periodoFacturacion->estaAbierto()) {
            return back()->withErrors([
                'error' => 'No se puede eliminar. El período está cerrado.'
            ]);
        }

        $periodoId = $factura->periodo_facturacion_id;
        $factura->delete();

        return redirect()
            ->route('periodos.show', $periodoId)
            ->with('success', 'Factura eliminada. Factores recalculados.');
    }
}