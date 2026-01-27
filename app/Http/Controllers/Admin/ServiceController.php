<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\service;
use App\Models\ServiceAvailability;
use App\Models\ServiceBooking;
use App\Models\ServiceContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return Service::get();
    }

    public function show(Service $service)
    {
        return $service->load(['contexts', 'availabilities']);
    }

    public function store(Request $request)
    {
        // $data = $request->validate([
        //     'user_id'     => 'required|exists:users,id',
        //     'name'        => 'required|string|max:255',
        //     'description' => 'nullable|string',
        //     'base_price'  => 'required|numeric|min:0',
        //     'active'      => 'boolean',
        // ]);

        // return Service::create($data);
        $data = $request->validate([
            'service.user_id'     => 'required|exists:users,id',
            'service.name' => 'required|string|max:255',
            'service.description' => 'nullable|string',
            'service.base_price' => 'required|numeric|min:0',

            'contexts' => 'required|array|min:1',
            'contexts.*.context' => 'required|in:public,local',
            'contexts.*.local_id' => 'nullable|exists:locals,id',
            'contexts.*.active' => 'boolean',
            'contexts.*.price_override' => 'nullable|numeric|min:0',

            'availabilities' => 'nullable|array',
            'availabilities.*.start_date' => 'required|date',
            'availabilities.*.end_date'   => 'required|date|after_or_equal:availabilities.*.start_date',
            'availabilities.*.start_time' => 'required',
            'availabilities.*.end_time'   => 'required',
            'availabilities.*.days'       => 'required|array',
        ]);

        return DB::transaction(function () use ($data, $request) {

            $service = Service::create([
                ...$data['service'],
                'active' => false
            ]);

            foreach ($data['contexts'] as $context) {
                $service->contexts()->create($context);
            }

            if (!empty($data['availabilities'])) {
                foreach ($data['availabilities'] as $range) {
                    $start = \Carbon\Carbon::parse($range['start_date']);
                    $end   = \Carbon\Carbon::parse($range['end_date']);

                    while ($start->lte($end)) {

                        if (in_array($start->dayOfWeekIso, $range['days'])) {
                            $service->availabilities()->create([
                                'date'       => $start->toDateString(),
                                'start_time' => $range['start_time'],
                                'end_time'   => $range['end_time'],
                                'available'  => true,
                            ]);
                        }
                        $start->addDay();
                    }
                }
            }

            if ($service->contexts()->where('active', true)->exists()) {
                $service->update(['active' => true]);
            }

            return response()->json($service->load(['contexts', 'availabilities']));
        });
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price'  => 'sometimes|numeric|min:0',
            'active'      => 'sometimes|boolean',
        ]);

        $service->update($data);

        return $service;
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json([
            'message' => 'Servicio eliminado'
        ]);
    }
}
