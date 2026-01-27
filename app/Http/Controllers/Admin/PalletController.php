<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PalletController extends Controller
{
    public function index()
    {
        return Pallet::get();
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'pallet_code' => ['required', 'string', 'max:20', 'unique:pallets,pallet_code'],
            'warehouse_location' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:empty,open,full'],
            'max_packages_capacity' => ['required', 'integer', 'min:1'],
        ]);

        $pallet = Pallet::create($data);

        return response()->json([
            'message' => 'Pallet insertado correctamente.',
            'local'   => $pallet
        ], 201);
    }

    public function show(Pallet $pallet)
    {
        return $pallet;
    }

    public function update(Request $request, Pallet $pallet)
    {
        $data = $request->validate([
            'pallet_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('pallets', 'pallet_code')->ignore($pallet->id),
            ],
            'warehouse_location' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:empty,open,full'],
            'max_packages_capacity' => ['required', 'integer', 'min:1'],
        ]);

        $pallet->update($data);

        return response()->json([
            'message' => 'Pallet actualizado.',
            'local' => $pallet
        ]);
    }

    public function destroy(Pallet $pallet)
    {
        $pallet->delete();

        return response()->json([
            'message' => 'Pallet eliminado.'
        ]);
    }
}
