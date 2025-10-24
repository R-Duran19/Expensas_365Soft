<?php

namespace App\Http\Controllers;

use App\Models\GrupoMedidor;
use App\Models\Medidor;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class GrupoMedidorController extends Controller
{
    public function index()
    {
        $grupos = GrupoMedidor::with(['medidor', 'propiedades'])
            ->withCount('propiedades')
            ->orderBy('nombre')
            ->paginate(15);

        return Inertia::render('GruposMedidores/Index', [
            'grupos' => $grupos
        ]);
    }

    public function create()
    {
        // Medidores que NO están asignados a grupos
        $medidoresDisponibles = Medidor::doesntHave('grupoMedidor')
            ->activos()
            ->with('propiedad')
            ->orderBy('numero_medidor')
            ->get();

        $propiedades = Propiedad::orderBy('nombre')->get();

        return Inertia::render('GruposMedidores/Create', [
            'medidores' => $medidoresDisponibles,
            'propiedades' => $propiedades
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'medidor_id' => 'required|exists:medidores,id|unique:grupos_medidores,medidor_id',
            'metodo_prorrateo' => 'required|in:partes_iguales,por_m2,porcentaje_custom',
            'propiedades' => 'required|array|min:2',
            'propiedades.*.id' => 'required|exists:propiedades,id',
            'propiedades.*.porcentaje' => 'required_if:metodo_prorrateo,porcentaje_custom|nullable|numeric|min:0|max:100',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            // Validar porcentajes si es necesario
            if ($validated['metodo_prorrateo'] === 'porcentaje_custom') {
                $sumaPorcentajes = collect($validated['propiedades'])->sum('porcentaje');
                if (abs($sumaPorcentajes - 100) > 0.01) {
                    return back()->withErrors([
                        'propiedades' => 'Los porcentajes deben sumar 100%'
                    ])->withInput();
                }
            }

            // Crear grupo
            $grupo = GrupoMedidor::create([
                'nombre' => $validated['nombre'],
                'medidor_id' => $validated['medidor_id'],
                'metodo_prorrateo' => $validated['metodo_prorrateo'],
                'activo' => true,
                'observaciones' => $validated['observaciones'] ?? null
            ]);

            // Asociar propiedades
            foreach ($validated['propiedades'] as $propData) {
                $grupo->propiedades()->attach($propData['id'], [
                    'porcentaje' => $propData['porcentaje'] ?? null
                ]);
            }

            DB::commit();

            return redirect()
                ->route('grupos-medidores.show', $grupo)
                ->with('success', 'Grupo de medidores creado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(GrupoMedidor $grupoMedidor)
    {
        $grupoMedidor->load([
            'medidor.propiedad',
            'propiedades.tipoPropiedad'
        ]);

        return Inertia::render('GruposMedidores/Show', [
            'grupo' => $grupoMedidor,
            'porcentajes_validos' => $grupoMedidor->porcentajesValidos()
        ]);
    }

    public function edit(GrupoMedidor $grupoMedidor)
    {
        $grupoMedidor->load(['medidor', 'propiedades']);

        $propiedades = Propiedad::orderBy('nombre')->get();

        return Inertia::render('GruposMedidores/Edit', [
            'grupo' => $grupoMedidor,
            'propiedades' => $propiedades
        ]);
    }

    public function update(Request $request, GrupoMedidor $grupoMedidor)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'metodo_prorrateo' => 'required|in:partes_iguales,por_m2,porcentaje_custom',
            'propiedades' => 'required|array|min:2',
            'propiedades.*.id' => 'required|exists:propiedades,id',
            'propiedades.*.porcentaje' => 'required_if:metodo_prorrateo,porcentaje_custom|nullable|numeric|min:0|max:100',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            // Validar porcentajes
            if ($validated['metodo_prorrateo'] === 'porcentaje_custom') {
                $sumaPorcentajes = collect($validated['propiedades'])->sum('porcentaje');
                if (abs($sumaPorcentajes - 100) > 0.01) {
                    return back()->withErrors([
                        'propiedades' => 'Los porcentajes deben sumar 100%'
                    ])->withInput();
                }
            }

            // Actualizar grupo
            $grupoMedidor->update([
                'nombre' => $validated['nombre'],
                'metodo_prorrateo' => $validated['metodo_prorrateo'],
                'activo' => $validated['activo'] ?? true,
                'observaciones' => $validated['observaciones'] ?? null
            ]);

            // Sincronizar propiedades
            $syncData = [];
            foreach ($validated['propiedades'] as $propData) {
                $syncData[$propData['id']] = [
                    'porcentaje' => $propData['porcentaje'] ?? null
                ];
            }
            $grupoMedidor->propiedades()->sync($syncData);

            DB::commit();

            return redirect()
                ->route('grupos-medidores.show', $grupoMedidor)
                ->with('success', 'Grupo actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(GrupoMedidor $grupoMedidor)
    {
        try {
            $nombre = $grupoMedidor->nombre;
            $grupoMedidor->delete();

            return redirect()
                ->route('grupos-medidores.index')
                ->with('success', "Grupo '{$nombre}' eliminado exitosamente.");
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al eliminar grupo: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle activo/inactivo
     */
    public function toggleActivo(GrupoMedidor $grupoMedidor)
    {
        $grupoMedidor->activo = !$grupoMedidor->activo;
        $grupoMedidor->save();

        $estado = $grupoMedidor->activo ? 'activado' : 'desactivado';

        return back()->with('success', "Grupo {$estado} exitosamente.");
    }
}