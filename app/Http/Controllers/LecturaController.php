<?php

// app/Http/Controllers/LecturaController.php
namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Medidor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LecturaController extends Controller
{
    public function index()
    {
        $lecturas = Lectura::with(['medidor.propiedad', 'usuario'])
            ->orderBy('fecha_lectura', 'desc')
            ->paginate(20);

        return Inertia::render('Lecturas', [
            'lecturas' => $lecturas
        ]);
    }

    public function create()
    {
        $medidores = Medidor::with(['propiedad', 'ultimaLectura'])
            ->activos()
            ->orderBy('numero_medidor')
            ->get();

        $mesActual = Carbon::now()->format('Y-m');

        return Inertia::render('Lecturas/Create', [
            'medidores' => $medidores,
            'mesActual' => $mesActual
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'medidor_id' => 'required|exists:medidores,id',
            'lectura_actual' => 'required|integer|min:0',
            'fecha_lectura' => 'required|date',
            'mes_periodo' => 'required|string',
            'observaciones' => 'nullable|string'
        ]);

        DB::transaction(function () use ($validated) {
            // Obtener medidor
            $medidor = Medidor::find($validated['medidor_id']);
            
            // Obtener última lectura para calcular lectura_anterior
            $ultimaLectura = $medidor->ultimaLectura();
            $lecturaAnterior = $ultimaLectura ? $ultimaLectura->lectura_actual : 0;

            // Verificar que no exista lectura para este mes
            $existeLectura = Lectura::where('medidor_id', $validated['medidor_id'])
                ->where('mes_periodo', $validated['mes_periodo'])
                ->exists();

            if ($existeLectura) {
                throw new \Exception('Ya existe una lectura para este medidor en el mes seleccionado.');
            }

            // Validar que lectura_actual sea mayor que lectura_anterior
            if ($validated['lectura_actual'] < $lecturaAnterior) {
                throw new \Exception('La lectura actual no puede ser menor que la lectura anterior.');
            }

            // Crear lectura
            Lectura::create([
                'medidor_id' => $validated['medidor_id'],
                'lectura_actual' => $validated['lectura_actual'],
                'lectura_anterior' => $lecturaAnterior,
                'fecha_lectura' => $validated['fecha_lectura'],
                'mes_periodo' => $validated['mes_periodo'],
                'usuario_id' => auth()->id(),
                'observaciones' => $validated['observaciones']
            ]);
        });

        return redirect()->route('lecturas.index')
            ->with('success', 'Lectura registrada exitosamente.');
    }

    public function getUltimaLectura(Medidor $medidor)
    {
        $ultimaLectura = $medidor->ultimaLectura();

        return response()->json([
            'lectura_anterior' => $ultimaLectura ? $ultimaLectura->lectura_actual : 0,
            'fecha_ultima_lectura' => $ultimaLectura ? $ultimaLectura->fecha_lectura : null
        ]);
    }

    public function destroy(Lectura $lectura)
    {
        // Verificar si es la última lectura antes de eliminar
        $esUltima = $lectura->medidor->ultimaLectura()->id === $lectura->id;
        
        if ($esUltima) {
            return redirect()->route('lecturas.index')
                ->withErrors(['error' => 'No se puede eliminar la última lectura del medidor.']);
        }

        $lectura->delete();

        return redirect()->route('lecturas.index')
            ->with('success', 'Lectura eliminada exitosamente.');
    }
}
