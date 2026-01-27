<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\service;
use App\Models\ServiceAvailability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{

    public function store(Request $request, Service $service)
    {
        if (!$service->active) {
            return response()->json(['message' => 'Servicio no disponible'], 409);
        }

        $data = $request->validate([
            'date'       => 'required|date',
            'start_time' => 'required',
            'end_time'   => 'required',
            'available'  => 'boolean',
        ]);

        return $service->availabilities()->create($data);
    }

    public function storeRange(Request $request, Service $service)
    {
        if (!$service->active) {
            return response()->json(['message' => 'Servicio no disponible'], 409);
        }

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time'   => 'required',
            'days'       => 'required|array',
        ]);

        $start = \Carbon\Carbon::parse($data['start_date']);
        $end   = \Carbon\Carbon::parse($data['end_date']);

        $created = [];

        while ($start->lte($end)) {
            if (in_array($start->dayOfWeekIso, $data['days'])) {

                $created[] = $service->availabilities()->create([
                    'date'       => $start->toDateString(),
                    'start_time' => $data['start_time'],
                    'end_time'   => $data['end_time'],
                    'available'  => true,
                ]);
            }

            $start->addDay();
        }

        return response()->json([
            'message' => 'Disponibilidades creadas',
            'count'   => count($created),
        ]);
    }


    public function update(Request $request, ServiceAvailability $availability)
    {
        $service = service::findOrFail($availability->service_id);

        if (!$service->active) {
            return response()->json(['message' => 'Servicio no disponible'], 409);
        }

        $data = $request->validate([
            'date'       => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time'   => 'sometimes',
            'available'  => 'boolean',
        ]);

        $availability->update($data);

        return $availability;
    }

    public function destroy(ServiceAvailability $availability)
    {
        $availability->delete();

        return response()->json([
            'message' => 'Disponibilidad eliminada'
        ]);
    }
}
