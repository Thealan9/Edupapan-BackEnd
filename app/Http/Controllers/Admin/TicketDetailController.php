<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\StockTransaction;
use App\Models\Ticket;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketDetailController extends Controller
{
    public function CreateSolutionDetailTicketDamage(Request $request, TicketDetail $detail)
    {
        $validStatuses = ['completed','partially_completed','cancelled','return'];
        if(!in_array($detail->ticket->status,$validStatuses)){
            return response()->json(['message' => 'Debe finalizar el ticket primero.'], 422);
        }

        if ($detail->status !== 'damaged' || $detail->ticket->type !== 'sale') {
            return response()->json(['message' => 'Status o ticket no valido'], 422);
        }

        $data = $request->validate([
            'confirm' => ['required', 'boolean'],
            'partial' => ['nullable', 'boolean'],
            'assigned_to' => ['required_if:confirm,true','nullable', 'exists:users,id']
        ]);

       //response()->json(['message' => $data]);
        // si esta dañado
        if (isset($data['confirm']) && $data['confirm'] === true) {
            //si es parcial
            if (isset($data['partial']) && $data['partial'] === true) {

                DB::transaction(function () use ($detail, $data, $request, &$ticket) {
                    $detail->update([
                        'status' => 'cancelled',
                    ]);
                    $ticket = Ticket::create([
                        'type' => 'partial_damaged',
                        'status' => 'pending',
                        'assigned_to' => $data['assigned_to'],
                        'quantity' => 1,
                        'description' => 'Ticket generado por daño parcial del paquete ID: ' . $detail->package->id,
                    ]);
                    StockTransaction::create([
                        'book_id'   => $detail->package->book_id,
                        'user_id'   => $request->user()->id,
                        'ticket_id' => $ticket->id,
                    ]);
                    TicketDetail::create([
                        'ticket_id' => $ticket->id,
                        'package_id' => $detail->package->id,
                        'status' => 'pending',
                    ]);
                });
                return response()->json([
                    'message' => 'ticket de retiro por daño parcial creado.'
                ]);
            } else
            //si es perdido totalmente
            {
                DB::transaction(function () use ($detail, $data, $request, &$ticket) {
                    $detail->update([
                        'status' => 'cancelled',
                    ]);
                    $ticket = Ticket::create([
                        'type' => 'damaged',
                        'status' => 'pending',
                        'assigned_to' => $data['assigned_to'],
                        'quantity' => 1,
                        'description' => 'Ticket generado por perdida total del paquete ID: ' . $detail->package->id,
                    ]);
                    StockTransaction::create([
                        'book_id'   => $detail->package->book_id,
                        'user_id'   => $request->user()->id,
                        'ticket_id' => $ticket->id,
                    ]);
                    TicketDetail::create([
                        'ticket_id' => $ticket->id,
                        'package_id' => $detail->package->id,
                        'status' => 'pending',
                    ]);
                });
                return response()->json([
                    'message' => 'ticket de retiro por perdida total creado.'
                ]);
            }
        } else {
            $detail->update([
                'status' => 'cancelled',
            ]);
            $detail->package->update([
                'status' => 'available',
            ]);
            return response()->json([
                'message' => 'Operación cancelada, el paquete vuelve a estar disponible'
            ]);
        }
    }

    public function CreateSolutionDetailTicketMissing(Request $request, TicketDetail $detail)
    {
        $validStatuses = ['completed','partially_completed','cancelled','return'];
        if(!in_array($detail->ticket->status,$validStatuses)){
            return response()->json(['message' => 'Debe finalizar el ticket primero.'], 422);
        }

        if ($detail->status !== 'missing' || $detail->ticket->type !== 'sale') {
            return response()->json(['message' => 'Status o ticket no valido'], 422);
        }

        $data = $request->validate([
            'confirm' => ['required', 'boolean']
        ]);

        // si esta perdido
        if (isset($data['confirm']) && $data['confirm'] === true) {
            $detail->update([
                'status' => 'cancelled',
            ]);
            $detail->package->update([
                'status' => 'missing',
            ]);
            return response()->json([
                'message' => 'El paquete ha sido marcado como perdido'
            ]);
        } else {
            $detail->update([
                'status' => 'cancelled',
            ]);
            $detail->package->update([
                'status' => 'available',
            ]);
            return response()->json([
                'message' => 'El paquete vuelve a estar disponible'
            ]);
        }
    }
    //public function CreateSolutionDetailTicketOther(Request $request, TicketDetail $detail){}



}
