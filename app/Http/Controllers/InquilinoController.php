<?php

namespace App\Http\Controllers;

use App\Models\Inquilino;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InquilinoController extends Controller
{
    public function index(Request $request)
    {
        $query = Inquilino::withCount('propiedades');

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $query->buscar($request->search);
        }

        // Filtro por estado
        if ($request->has('activo') && $request->activo !== null) {
            $activo = $request->activo === 'true' || $request->activo === true;
            $query->where('activo', $activo);
        }

        // Filtro por contrato vigente
        if ($request->has('contrato_vigente') && $request->contrato_vigente !== null) {
            $vigente = $request->contrato_vigente === 'true' || $request->contrato_vigente === true;
            if ($vigente) {
                $query->conContratoVigente();
            }
            // Si no es vigente, mostramos todos (sin filtro específico)
        }

        // Ordenamiento
        $orderBy = $request->get('orderBy', 'nombre_completo');
        $orderDirection = $request->get('orderDirection', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        $inquilinos = $query->paginate($request->get('perPage', 15))
            ->withQueryString();

        // Cargar propiedades para cada inquilino
        $inquilinos->getCollection()->each(function ($inquilino) {
            $inquilino->load(['propiedades' => function ($query) {
                $query->with(['tipoPropiedad'])
                     ->withPivot([
                         'fecha_inicio_contrato',
                         'fecha_fin_contrato',
                         'es_inquilino_principal',
                         'observaciones'
                     ])
                     ->orderBy('inquilino_propiedad.fecha_inicio_contrato', 'desc');
            }]);
        });

        return Inertia::render('Propietarios', [
            'inquilinos' => $inquilinos,
            'activeTab' => 'inquilinos',
            'filters' => $request->only(['search', 'activo', 'contrato_vigente', 'orderBy', 'orderDirection'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'nullable|string|max:20|unique:inquilinos,ci',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
            'propiedades' => 'required|array|min:1',
            'propiedades.*.propiedad_id' => 'required|exists:propiedades,id',
            'propiedades.*.fecha_inicio_contrato' => 'required|date',
            'propiedades.*.fecha_fin_contrato' => 'nullable|date',
            'propiedades.*.es_inquilino_principal' => 'boolean',
            'propiedades.*.observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Crear el inquilino
            $inquilino = Inquilino::create([
                'nombre_completo' => $validated['nombre_completo'],
                'ci' => $validated['ci'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'email' => $validated['email'] ?? null,
                'activo' => $validated['activo'] ?? true,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Verificar que no haya múltiples inquilinos principales en la misma propiedad
            $propiedadesPrincipales = collect($validated['propiedades'])
                ->filter(fn($prop) => $prop['es_inquilino_principal'] ?? true)
                ->pluck('propiedad_id');

            if ($propiedadesPrincipales->isNotEmpty()) {
                $conflictos = DB::table('inquilino_propiedad')
                    ->join('inquilinos', 'inquilino_propiedad.inquilino_id', '=', 'inquilinos.id')
                    ->whereIn('inquilino_propiedad.propiedad_id', $propiedadesPrincipales)
                    ->where('inquilino_propiedad.es_inquilino_principal', true)
                    ->whereNull('inquilino_propiedad.fecha_fin_contrato')
                    ->where('inquilinos.activo', true)
                    ->count();

                if ($conflictos > 0) {
                    DB::rollBack();
                    return back()
                        ->withErrors(['propiedades' => 'Ya existe un inquilino principal activo en una de las propiedades seleccionadas.'])
                        ->withInput();
                }
            }

            // Asignar propiedades
            $propiedadesData = [];
            foreach ($validated['propiedades'] as $propiedad) {
                $propiedadesData[$propiedad['propiedad_id']] = [
                    'fecha_inicio_contrato' => $propiedad['fecha_inicio_contrato'],
                    'fecha_fin_contrato' => $propiedad['fecha_fin_contrato'] ?? null,
                    'es_inquilino_principal' => $propiedad['es_inquilino_principal'] ?? true,
                      'observaciones' => $propiedad['observaciones'] ?? null,
                ];
            }

            $inquilino->propiedades()->attach($propiedadesData);

            DB::commit();

            return redirect()->route('propietarios.index', ['activeTab' => 'inquilinos'])
                ->with('success', 'Inquilino creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Error al crear el inquilino: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, Inquilino $inquilino)
    {
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'nullable|string|max:20|unique:inquilinos,ci,' . $inquilino->id,
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'activo' => 'boolean',
            'observaciones' => 'nullable|string',
            'propiedades' => 'required|array|min:1',
            'propiedades.*.propiedad_id' => 'required|exists:propiedades,id',
            'propiedades.*.fecha_inicio_contrato' => 'required|date',
            'propiedades.*.fecha_fin_contrato' => 'nullable|date',
            'propiedades.*.es_inquilino_principal' => 'boolean',
            'propiedades.*.observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Actualizar datos del inquilino
            $inquilino->update([
                'nombre_completo' => $validated['nombre_completo'],
                'ci' => $validated['ci'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'email' => $validated['email'] ?? null,
                'activo' => $validated['activo'] ?? true,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Verificar conflictos de inquilinos principales (excluyendo al inquilino actual)
            $propiedadesPrincipales = collect($validated['propiedades'])
                ->filter(fn($prop) => $prop['es_inquilino_principal'] ?? true)
                ->pluck('propiedad_id');

            if ($propiedadesPrincipales->isNotEmpty()) {
                $conflictos = DB::table('inquilino_propiedad')
                    ->join('inquilinos', 'inquilino_propiedad.inquilino_id', '=', 'inquilinos.id')
                    ->whereIn('inquilino_propiedad.propiedad_id', $propiedadesPrincipales)
                    ->where('inquilino_propiedad.es_inquilino_principal', true)
                    ->whereNull('inquilino_propiedad.fecha_fin_contrato')
                    ->where('inquilinos.activo', true)
                    ->where('inquilinos.id', '!=', $inquilino->id)
                    ->count();

                if ($conflictos > 0) {
                    DB::rollBack();
                    return back()
                        ->withErrors(['propiedades' => 'Ya existe otro inquilino principal activo en una de las propiedades seleccionadas.'])
                        ->withInput();
                }
            }

            // Sincronizar propiedades
            $propiedadesData = [];
            foreach ($validated['propiedades'] as $propiedad) {
                $propiedadesData[$propiedad['propiedad_id']] = [
                    'fecha_inicio_contrato' => $propiedad['fecha_inicio_contrato'],
                    'fecha_fin_contrato' => $propiedad['fecha_fin_contrato'] ?? null,
                    'es_inquilino_principal' => $propiedad['es_inquilino_principal'] ?? true,
                      'observaciones' => $propiedad['observaciones'] ?? null,
                ];
            }

            $inquilino->propiedades()->sync($propiedadesData);

            DB::commit();

            return redirect()->route('propietarios.index', ['activeTab' => 'inquilinos'])
                ->with('success', 'Inquilino actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Error al actualizar el inquilino: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Inquilino $inquilino)
    {
        try {
            DB::beginTransaction();

            // Soft delete
            $inquilino->delete();

            DB::commit();

            return redirect()->route('propietarios.index', ['activeTab' => 'inquilinos'])
                ->with('success', 'Inquilino eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('propietarios.index', ['activeTab' => 'inquilinos'])
                ->withErrors([
                    'error' => 'Error al eliminar el inquilino: ' . $e->getMessage()
                ]);
        }
    }

    public function show(Inquilino $inquilino)
    {
        $inquilino->load(['propiedades' => function ($query) {
            $query->with(['tipoPropiedad'])
                 ->withPivot([
                     'fecha_inicio_contrato',
                     'fecha_fin_contrato',
                     'es_inquilino_principal',
                     'observaciones'
                 ])
                 ->orderBy('inquilino_propiedad.fecha_inicio_contrato', 'desc');
        }]);

        return response()->json([
            'inquilino' => $inquilino
        ]);
    }

    public function asignarPropiedad(Request $request, Inquilino $inquilino)
    {
        $validated = $request->validate([
            'propiedad_id' => 'required|exists:propiedades,id',
            'fecha_inicio_contrato' => 'required|date',
            'fecha_fin_contrato' => 'nullable|date',
            'es_inquilino_principal' => 'boolean',
            'observaciones' => 'nullable|string',
        ]);

        try {
            // Si es inquilino principal, verificar que no haya conflictos
            if ($validated['es_inquilino_principal'] ?? true) {
                $existente = DB::table('inquilino_propiedad')
                    ->join('inquilinos', 'inquilino_propiedad.inquilino_id', '=', 'inquilinos.id')
                    ->where('inquilino_propiedad.propiedad_id', $validated['propiedad_id'])
                    ->where('inquilino_propiedad.es_inquilino_principal', true)
                    ->whereNull('inquilino_propiedad.fecha_fin_contrato')
                    ->where('inquilinos.activo', true)
                    ->where('inquilinos.id', '!=', $inquilino->id)
                    ->exists();

                if ($existente) {
                    return back()->withErrors([
                        'propiedad_id' => 'Ya existe un inquilino principal activo en esta propiedad.'
                    ]);
                }
            }

            $inquilino->propiedades()->attach($validated['propiedad_id'], [
                'fecha_inicio_contrato' => $validated['fecha_inicio_contrato'],
                'fecha_fin_contrato' => $validated['fecha_fin_contrato'] ?? null,
                'es_inquilino_principal' => $validated['es_inquilino_principal'] ?? true,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return back()->with('success', 'Propiedad asignada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al asignar la propiedad: ' . $e->getMessage()
            ]);
        }
    }

    public function desasignarPropiedad(Inquilino $inquilino, Propiedad $propiedad)
    {
        try {
            // Verificar que la propiedad esté asignada
            $asignacion = $inquilino->propiedades()
                ->wherePivot('propiedad_id', $propiedad->id)
                ->first();

            if (!$asignacion) {
                return back()->withErrors([
                    'error' => 'Esta propiedad no está asignada a este inquilino.'
                ]);
            }

            // Actualizar fecha_fin en lugar de eliminar
            $inquilino->propiedades()->updateExistingPivot($propiedad->id, [
                'fecha_fin_contrato' => now(),
            ]);

            return back()->with('success', 'Propiedad desasignada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al desasignar la propiedad: ' . $e->getMessage()
            ]);
        }
    }
}