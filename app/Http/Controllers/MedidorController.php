<?php

namespace App\Http\Controllers;

use App\Models\Medidor;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;

class MedidorController extends Controller
{
    public function index()
    {
        $medidores = Medidor::with(['propiedad.tipoPropiedad'])
            ->orderBy('id')
            ->paginate(10);

        $propiedades = Propiedad::with('tipoPropiedad')
            ->requierenMedidor() // Solo propiedades que requieren medidor
            ->whereDoesntHave('medidor')
            ->activas()
            ->orderBy('codigo')
            ->get();

        return Inertia::render('Medidores', [
            'medidores' => $medidores,
            'propiedades' => $propiedades
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_medidor' => 'required|string|unique:medidores',
            'ubicacion' => 'nullable|string',
            'propiedad_id' => [
                'required',
                'exists:propiedades,id',
                'unique:medidores,propiedad_id', // Validar que la propiedad no tenga medidor
                function ($attribute, $value, $fail) {
                    $propiedad = Propiedad::find($value);
                    if ($propiedad && !$propiedad->requiereMedidor()) {
                        $fail('Esta propiedad no requiere medidor.');
                    }
                },
            ],
            'observaciones' => 'nullable|string'
        ]);

        Medidor::create($validated);

        return redirect()->route('medidores.index')
            ->with('success', 'Medidor creado exitosamente.');
    }

    public function update(Request $request, Medidor $medidor)
    {
        $validated = $request->validate([
            'numero_medidor' => 'required|string|unique:medidores,numero_medidor,' . $medidor->id,
            'ubicacion' => 'nullable|string',
            'propiedad_id' => [
                'required',
                'exists:propiedades,id',
                'unique:medidores,propiedad_id,' . $medidor->id,
                function ($attribute, $value, $fail) {
                    $propiedad = Propiedad::find($value);
                    if ($propiedad && !$propiedad->requiereMedidor()) {
                        $fail('Esta propiedad no requiere medidor.');
                    }
                },
            ],
            'activo' => 'boolean',
            'observaciones' => 'nullable|string'
        ]);

        $medidor->update($validated);

        return redirect()->route('medidores.index')
            ->with('success', 'Medidor actualizado exitosamente.');
    }

    public function destroy(Medidor $medidor)
    {
        if ($medidor->lecturas()->exists()) {
            return redirect()->route('medidores.index')
                ->withErrors(['error' => 'No se puede eliminar un medidor con lecturas registradas.']);
        }

        $medidor->delete();

        return redirect()->route('medidores.index')
            ->with('success', 'Medidor eliminado exitosamente.');
    }

    public function buscarPropiedades(Request $request)
    {
        $termino = $request->input('q', '');
        $limit = $request->input('limit', 20);

        $propiedades = Propiedad::with('tipoPropiedad')
            ->requierenMedidor() // Solo propiedades que requieren medidor
            ->whereDoesntHave('medidor')
            ->activas()
            ->buscar($termino)
            ->orderBy('codigo')
            ->limit($limit)
            ->get();

        return response()->json($propiedades);
    }
    public function getActivos(): JsonResponse
    {
        $medidores = Medidor::with(['propiedad.tipoPropiedad']) // Relación en singular
            ->activos()
            ->orderBy('numero_medidor')
            ->get()
            ->map(function ($medidor) {
                return [
                    'id' => $medidor->id,
                    'numero_medidor' => $medidor->numero_medidor,
                    'ubicacion' => $medidor->ubicacion,
                    'tipo' => $medidor->tipo, // 'comercial' o 'domiciliario'
                    'lectura_anterior' => (float) ($medidor->ultimaLectura()?->lectura_actual ?? 0),
                    'fecha_ultima_lectura' => $medidor->ultimaLectura()?->fecha_lectura,
                    'propiedad' => [
                        'id' => $medidor->propiedad->id,
                        'codigo' => $medidor->propiedad->codigo,
                        // 'nombre' => $medidor->propiedad->nombre, // ← ESTE CAMPO NO EXISTE
                        'ubicacion' => $medidor->propiedad->ubicacion ?? null,
                        'tipo_propiedad' => $medidor->propiedad->tipoPropiedad->nombre ?? null,
                    ],
                ];
            });

        return response()->json($medidores);
    }
}