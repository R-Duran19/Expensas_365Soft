<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PropietarioController extends Controller
{
    /**
     * Mostrar lista de propietarios
     */
    public function index(Request $request)
    {
        $query = Propietario::with(['propiedades.tipoPropiedad'])
            ->withCount('propiedades');

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $query->buscar($request->search);
        }

        // Filtro por estado
        if ($request->has('activo') && $request->activo !== null) {
            $activo = $request->activo === 'true' || $request->activo === true;
            $query->where('activo', $activo);
        }

        // Ordenamiento
        $orderBy = $request->get('orderBy', 'nombre_completo');
        $orderDirection = $request->get('orderDirection', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        $propietarios = $query->paginate($request->get('perPage', 15))
            ->withQueryString();

        // Cargar propiedades disponibles para asignar
        $propiedades = Propiedad::with('tipoPropiedad')
            ->activas()
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'ubicacion', 'tipo_propiedad_id']);

        return Inertia::render('Propietarios', [
            'propietarios' => $propietarios,
            'propiedades' => $propiedades,
            'filters' => $request->only(['search', 'activo', 'orderBy', 'orderDirection'])
        ]);
    }

    /**
     * Almacenar nuevo propietario
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'nullable|string|max:20|unique:propietarios,ci',
            'nit' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion_externa' => 'nullable|string',
            'fecha_registro' => 'nullable|date',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
            'propiedad_id' => 'nullable|exists:propiedades,id',
            'porcentaje_participacion' => 'nullable|numeric|min:0|max:100',
            'fecha_inicio_propiedad' => 'nullable|date',
        ]);

        DB::beginTransaction();
        
        try {
            $propietario = Propietario::create([
                'nombre_completo' => $validated['nombre_completo'],
                'ci' => $validated['ci'] ?? null,
                'nit' => $validated['nit'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'email' => $validated['email'] ?? null,
                'direccion_externa' => $validated['direccion_externa'] ?? null,
                'fecha_registro' => $validated['fecha_registro'] ?? now(),
                'activo' => $validated['activo'] ?? true,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Si se asignó una propiedad al crear
            if ($request->propiedad_id) {
                $propietario->propiedades()->attach($request->propiedad_id, [
                    'porcentaje_participacion' => $request->porcentaje_participacion ?? 100,
                    'fecha_inicio' => $request->fecha_inicio_propiedad ?? now(),
                    'es_propietario_principal' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('propietarios.index')
                ->with('success', 'Propietario creado exitosamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Error al crear el propietario: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Mostrar detalles de un propietario
     */
    public function show(Propietario $propietario)
    {
        $propietario->load([
            'propiedades' => function ($query) {
                $query->with(['tipoPropiedad', 'inquilinoActivo'])
                    ->withPivot([
                        'porcentaje_participacion',
                        'fecha_inicio',
                        'fecha_fin',
                        'es_propietario_principal',
                        'observaciones'
                    ]);
            }
        ]);

        return response()->json([
            'propietario' => $propietario
        ]);
    }

    /**
     * Actualizar propietario
     */
    public function update(Request $request, Propietario $propietario)
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'nullable|string|max:20|unique:propietarios,ci,' . $propietario->id,
            'nit' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion_externa' => 'nullable|string',
            'fecha_registro' => 'nullable|date',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $propietario->update($validated);

            return redirect()->route('propietarios.index')
                ->with('success', 'Propietario actualizado exitosamente.');
                
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al actualizar el propietario: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Eliminar propietario (soft delete)
     */
/**
 * Eliminar propietario (soft delete)
 */
public function destroy(Propietario $propietario)
{
    try {
        DB::beginTransaction();

        // Verificar si tiene propiedades asignadas activas (sin fecha_fin)
        $propiedadesActivas = $propietario->propiedadesActivas()->count();
        
        if ($propiedadesActivas > 0) {
            return back()->withErrors([
                'error' => 'No se puede eliminar un propietario con propiedades asignadas activas. Primero debe desasignar todas las propiedades.'
            ]);
        }

        // Detach todas las propiedades antes de eliminar
        // Esto evita problemas con el cascadeOnDelete
        $propietario->propiedades()->detach();

        // Ahora sí eliminar el propietario (soft delete)
        $propietario->delete();

        DB::commit();

        return redirect()->route('propietarios.index')
            ->with('success', 'Propietario eliminado exitosamente.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors([
            'error' => 'Error al eliminar el propietario: ' . $e->getMessage()
        ]);
    }
}

    /**
     * Asignar propiedad a propietario
     */
    public function asignarPropiedad(Request $request, Propietario $propietario)
    {
        $validated = $request->validate([
            'propiedad_id' => 'required|exists:propiedades,id',
            'porcentaje_participacion' => 'nullable|numeric|min:0|max:100',
            'fecha_inicio' => 'nullable|date',
            'es_propietario_principal' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        // Verificar si ya está asignada sin fecha_fin
        $existente = $propietario->propiedades()
            ->wherePivot('propiedad_id', $validated['propiedad_id'])
            ->whereNull('propietario_propiedad.fecha_fin')
            ->exists();

        if ($existente) {
            return back()->withErrors([
                'error' => 'Esta propiedad ya está asignada a este propietario.'
            ]);
        }

        try {
            $propietario->propiedades()->attach($validated['propiedad_id'], [
                'porcentaje_participacion' => $validated['porcentaje_participacion'] ?? 100,
                'fecha_inicio' => $validated['fecha_inicio'] ?? now(),
                'es_propietario_principal' => $validated['es_propietario_principal'] ?? true,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return back()->with('success', 'Propiedad asignada exitosamente.');
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al asignar la propiedad: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Desasignar propiedad (marcar fecha_fin)
     */
/**
 * Desasignar propiedad (marcar fecha_fin)
 */
public function desasignarPropiedad(Propietario $propietario, Propiedad $propiedad)
{
    try {
        // Verificar que la propiedad esté asignada sin fecha_fin
        $asignacion = $propietario->propiedades()
            ->wherePivot('propiedad_id', $propiedad->id)
            ->whereNull('propietario_propiedad.fecha_fin')
            ->first();

        if (!$asignacion) {
            return back()->withErrors([
                'error' => 'Esta propiedad no está asignada activamente a este propietario.'
            ]);
        }

        $propietario->propiedades()->updateExistingPivot($propiedad->id, [
            'fecha_fin' => now(),
        ]);

        return back()->with('success', 'Propiedad desasignada exitosamente.');
        
    } catch (\Exception $e) {
        return back()->withErrors([
            'error' => 'Error al desasignar la propiedad: ' . $e->getMessage()
        ]);
    }
}
}