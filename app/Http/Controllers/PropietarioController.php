<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PropietarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Propietario::withCount('propiedades')
            ->whereNull('deleted_at');

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

        return Inertia::render('Propietarios', [
            'propietarios' => $propietarios,
            'filters' => $request->only(['search', 'activo', 'orderBy', 'orderDirection'])
        ]);
    }


 public function getPropiedades(Propietario $propietario)
{
    // Cargar propiedades del propietario
    $propietario->load([
        'propiedades' => function ($query) {
            $query->with(['tipoPropiedad'])
                ->withPivot([
                    'fecha_inicio',
                    'fecha_fin',
                    'es_propietario_principal',
                    'observaciones'
                ])
                ->orderBy('propietario_propiedad.fecha_inicio', 'desc');
        }
    ]);

    // Cargar propiedades disponibles para el dialog
    $propiedadesDisponibles = Propiedad::with('tipoPropiedad')
        ->activas()
        ->orderBy('codigo')
        ->get();

    return inertia()->render('Propietarios/PropiedadesView', [
        'propietario' => $propietario,
        'propiedades' => $propiedadesDisponibles // Pasar propiedades disponibles
    ]);
}


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
            'propiedades' => 'nullable|array',
            'propiedades.*.propiedad_id' => 'required|exists:propiedades,id',
            'propiedades.*.fecha_inicio' => 'required|date',
            'propiedades.*.es_propietario_principal' => 'boolean',
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

            // Asignar múltiples propiedades si existen
            if (isset($validated['propiedades']) && count($validated['propiedades']) > 0) {
                $propiedadesData = [];
                foreach ($validated['propiedades'] as $propiedad) {
                    $propiedadesData[$propiedad['propiedad_id']] = [
                        'fecha_inicio' => $propiedad['fecha_inicio'],
                        'es_propietario_principal' => $propiedad['es_propietario_principal']
                    ];
                }
                $propietario->propiedades()->attach($propiedadesData);
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

    public function getPropiedadesDisponibles()
    {
        $propiedades = Propiedad::with('tipoPropiedad')
            ->activas()
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'propiedades' => $propiedades
        ]);
    }

    // Y modifica el método show para que no cargue propiedades innecesariamente
    public function show(Propietario $propietario)
    {
        // Solo cargar las propiedades del propietario, no todas las disponibles
        $propietario->load([
            'propiedades' => function ($query) {
                $query->with(['tipoPropiedad'])
                    ->withPivot([
                        'fecha_inicio',
                        'fecha_fin',
                        'es_propietario_principal',
                        'observaciones'
                    ])
                    ->whereNull('propietario_propiedad.fecha_fin');
            }
        ]);

        return response()->json([
            'propietario' => $propietario
        ]);
    }

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
            'propiedades' => 'nullable|array',
            'propiedades.*.propiedad_id' => 'required|exists:propiedades,id',
            'propiedades.*.fecha_inicio' => 'required|date',
            'propiedades.*.es_propietario_principal' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $propietario->update($validated);

            // Sincronizar propiedades si se enviaron
            if (isset($validated['propiedades'])) {
                $propiedadesData = [];
                foreach ($validated['propiedades'] as $propiedad) {
                    $propiedadesData[$propiedad['propiedad_id']] = [
                        'fecha_inicio' => $propiedad['fecha_inicio'],
                        'es_propietario_principal' => $propiedad['es_propietario_principal']
                    ];
                }
                $propietario->propiedades()->sync($propiedadesData);
            } else {
                // Si no se enviaron propiedades, mantener las existentes
                // O si quieres eliminar todas: $propietario->propiedades()->detach();
            }

            DB::commit();

            return redirect()->route('propietarios.index')
                ->with('success', 'Propietario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Error al actualizar el propietario: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Propietario $propietario)
    {
        try {
            DB::beginTransaction();

            // Verificar si tiene propiedades asignadas activas (sin fecha_fin)
            $propiedadesActivas = $propietario->propiedades()
                ->whereNull('propietario_propiedad.fecha_fin')
                ->count();

            if ($propiedadesActivas > 0) {
                DB::rollBack();
                return redirect()->route('propietarios.index')
                    ->withErrors([
                        'error' => 'No se puede eliminar un propietario con propiedades asignadas activas. Primero debe desasignar todas las propiedades.'
                    ]);
            }

            $propietario->forceDelete();

            DB::commit();

            return redirect()->route('propietarios.index')
                ->with('success', 'Propietario eliminado permanentemente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('propietarios.index')
                ->withErrors([
                    'error' => 'Error al eliminar el propietario: ' . $e->getMessage()
                ]);
        }
    }

    public function asignarPropiedad(Request $request, Propietario $propietario)
    {
        $validated = $request->validate([
            'propiedad_id' => 'required|exists:propiedades,id',
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
