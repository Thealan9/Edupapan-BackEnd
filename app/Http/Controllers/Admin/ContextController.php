<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\service;
use App\Models\ServiceContext;
use Illuminate\Http\Request;
use SebastianBergmann\Environment\Console;

class ContextController extends Controller
{
    public function store(Request $request, service $service)
    {
        if(!$service->active){
            return response()->json(['message' => 'Servicio no disponible'], 409);
        }

        $data = $request->validate([
            'context'        => 'required|in:public,local',
            'local_id'       => 'nullable|exists:locals,id',
            'price_override' => 'nullable|numeric|min:0',
            'active'         => 'boolean',
        ]);

        return $service->contexts()->create($data);
    }

    public function update(Request $request, ServiceContext $context)
    {
        $service = service::findOrFail($context->service_id);

        if(!$service->active){
            return response()->json(['message' => 'Servicio no disponible'], 409);
        }

        $data = $request->validate([
            'price_override' => 'nullable|numeric|min:0',
            'active'         => 'boolean',
        ]);

        $context->update($data);

        return $context;
    }

    public function destroy(ServiceContext $context)
    {
        $context->delete();

        return response()->json([
            'message' => 'Contexto eliminado'
        ]);
    }
}
