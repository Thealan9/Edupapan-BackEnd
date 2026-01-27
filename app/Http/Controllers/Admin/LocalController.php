<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Local;
use Illuminate\Http\Request;

class LocalController extends Controller
{
    public function index()
    {
        return Local::with('user:id,name,email')->get();
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string'
        ]);

        // if ($request->user()->id == $data['user_id']) {
        //     return response()->json([
        //         'message' => 'No puedes asignarte locales a ti mismo'
        //     ], 422);
        // }

        $local = Local::create([
            'name'    => $data['name'],
            'address' => $data['address'],
            'user_id' => $data['user_id'],
            'active'  => true,
        ]);

        return response()->json([
            'message' => 'Local creado correctamente',
            'local'   => $local
        ], 201);
    }

    public function show(Local $local)
    {
        return $local->load('user:id,name,email');
    }

    public function update(Request $request, Local $local)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'address' => 'nullable|string',
            'active' => 'boolean'
        ]);

        $local->update($data);

        return response()->json([
            'message' => 'Local actualizado',
            'local' => $local
        ]);
    }

    public function destroy(Local $local)
    {
        $local->delete();

        return response()->json([
            'message' => 'Local eliminado'
        ]);
    }
}
