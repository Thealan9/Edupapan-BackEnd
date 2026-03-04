<?php

namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Ticket;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketDetailController extends Controller
{
    public function processDetail(Request $request, TicketDetail $detail)
    {
        $ticket = $detail->ticket;

        if ($ticket->status !== 'in_progress') {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        if ($detail->status !== 'pending') {
            return response()->json(['message' => 'Detalle ya procesado'], 422);
        }

        $data = $request->validate([
            'status' => ['required', 'in:completed,damaged,missing,other'],
            'description' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($detail, $data) {

            $detail->update([
                'status' => $data['status'],
                'description' => $data['description'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Paquete procesado'
        ]);
    }

    // caso en que falte paquete para venta
    public function addDetail(Ticket $ticket)
{

    $detailsToReplace = TicketDetail::where('ticket_id', $ticket->id)
        ->whereIn('status', ['damaged', 'missing', 'other','cancelled'])
        ->whereDoesntHave('replacements')
        ->with('package')
        ->get();

    if ($detailsToReplace->isEmpty()) {
        return response()->json(['message' => 'No hay huecos nuevos por cubrir.'], 422);
    }

    $addedCount = 0;

    DB::transaction(function () use ($ticket, $detailsToReplace, &$addedCount) {
        foreach ($detailsToReplace as $originalDetail) {

            $replacementPackage = Package::where('book_id', $originalDetail->package->book_id)
                ->where('status', 'available')
                ->where('book_quantity', $originalDetail->package->book_quantity)
                ->first();

            if ($replacementPackage) {
                TicketDetail::create([
                    'ticket_id'     => $ticket->id,
                    'package_id'    => $replacementPackage->id,
                    'parent_id'     => $originalDetail->id,
                    'status'        => 'pending',
                    'book_quantity' => $replacementPackage->book_quantity,
                    'price'         => $originalDetail->price,
                    'description'   => null
                ]);

                $replacementPackage->update(['status' => 'reserved']);
                $addedCount++;
            }
        }
    });

    return response()->json(['message' => "Se añadieron {$addedCount} reemplazos."]);
}

    private function getAvailablePackages(int $bookId): int
    {
        return Package::where('book_id', $bookId)
            ->where('status', 'available')
            ->count();
    }
}
