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
            'message' => 'Detalle registrado'
        ]);
    }

    // caso en que falte paquete para venta
    public function addDetail(Ticket $ticket)
    {
        $bookId = $ticket->stockTransactions()->first()->book_id;
        $avaible_books = $this->getAvailablePackages($bookId);
        $details = $ticket->details;
        $total = $ticket->quantity;
        $summary = [
            'completed' => $details->where('status', 'completed')->count(),
        ];
        $missing = $total - $summary['completed'];

        if ($summary['completed'] < $total && $avaible_books >= $missing) {
            $faltantes = Package::where('book_id', $bookId)
                ->where('status', 'available')
                ->take($missing)
                ->pluck('id');
            DB::transaction(function () use ($ticket, $faltantes) {
                foreach ($faltantes as $id) {
                    TicketDetail::create([
                        'ticket_id' => $ticket->id,
                        'package_id' => $id,
                        'status' => 'pending',
                        'moved_to_pallet' => null,
                        'description' => null,

                    ]);
                    Package::where('id', $id)->update(['status' => 'reserved']);
                }
            });
            return response()->json([
                'message' => 'Se han agregado los reemplazos necesarios.',
                'cantidad_dispo' => $avaible_books,
                'faltante' => $missing]);
        }
        return response()->json(['message' => 'No hay suficientes paquetes.'],409);
    }

    private function getAvailablePackages(int $bookId): int
    {
        return Package::where('book_id', $bookId)
            ->where('status', 'available')
            ->count();
    }
}
