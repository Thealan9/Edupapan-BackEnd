<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceAvailability;
use App\Models\ServiceBooking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        // return ServiceBooking::with([
        //     'service',
        //     'context',
        //     'user'
        // ])
        // ->latest()
        // ->paginate(20);
         return ServiceBooking::get();
    }

    public function show(ServiceBooking $booking)
    {
        return $booking->load(['service', 'context', 'user']);
    }

    public function updateStatus(Request $request, ServiceBooking $booking)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled'
        ]);

        $booking->update($data);

        return $booking;
    }

    public function cancel(ServiceBooking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        ServiceAvailability::where('service_id', $booking->service_id)
            ->where('date', $booking->date)
            ->update(['available' => true]);

        return response()->json(['message' => 'Reserva cancelada']);
    }
}
