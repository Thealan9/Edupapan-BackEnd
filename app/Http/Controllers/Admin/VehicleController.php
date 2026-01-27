<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index()
    {
        return Vehicle::get();
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'plate'        => ['required', 'string', 'max:20', 'unique:vehicles,plate'],
            'model'        => ['required', 'string', 'max:255'],
            'is_active'    => ['required', 'boolean'],
            'is_available' => ['required', 'boolean'],

        ]);

        $veh = Vehicle::create($data);

        return response()->json([
            'message' => 'Vehiculo insertado correctamente.',
            'local'   => $veh
        ], 201);
    }

    public function show(Vehicle $veh)
    {
        return $veh;
    }

    public function update(Request $request, Vehicle $veh)
    {
        $data = $request->validate([
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate')->ignore($veh->id),
            ],
            'model'        => ['required', 'string', 'max:255'],
            'is_active'    => ['required', 'boolean'],
            'is_available' => ['required', 'boolean'],
        ]);

        $veh->update($data);

        return response()->json([
            'message' => 'Vehiculo actualizado.',
            'local' => $veh
        ]);
    }

    public function destroy(Vehicle $veh)
    {
        $veh->delete();

        return response()->json([
            'message' => 'Vehiculo eliminado.'
        ]);
    }
}
