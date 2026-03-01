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

    $incompleteDetails = $ticket->details()
        ->where('status', '!=', 'completed')
        ->with('package')
        ->get();

    if ($incompleteDetails->isEmpty()) {
        return response()->json(['message' => 'No hay espacios vacíos para completar en este ticket.'], 422);
    }

    $addedCount = 0;
    $replacements = [];

    DB::transaction(function () use ($ticket, $incompleteDetails, &$addedCount, &$replacements) {
        foreach ($incompleteDetails as $detail) {
            $bookId = $detail->package->book_id;

            $replacementPackage = Package::where('book_id', $bookId)
                ->where('status', 'available')
                ->first();

            if ($replacementPackage) {
                TicketDetail::create([
                    'ticket_id'  => $ticket->id,
                    'package_id' => $replacementPackage->id,
                    'status'     => 'pending',
                    'moved_to_pallet' => null,
                    'description'     => null,
                ]);

                $replacementPackage->update(['status' => 'reserved']);

                $addedCount++;
                $replacements[] = [
                    'book_id' => $bookId,
                    'new_package_id' => $replacementPackage->id
                ];
            }
        }
    });

    if ($addedCount > 0) {
        return response()->json([
            'message' => "Se han agregado {$addedCount} paquetes de reemplazo.",
            'replacements' => $replacements
        ]);
    }

    return response()->json([
        'message' => 'No se encontraron paquetes disponibles en el almacén para los libros faltantes.'
    ], 409);
}

    private function getAvailablePackages(int $bookId): int
    {
        return Package::where('book_id', $bookId)
            ->where('status', 'available')
            ->count();
    }
}
