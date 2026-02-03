<?php

namespace App\Http\Controllers\Warehouseman;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Ticket;
use App\Models\TicketDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function accept(Ticket $ticket)
    {
        if ($ticket->status !== 'pending') {
            return response()->json([
                'message' => 'El ticket no puede ser aceptado'
            ], 422);
        }

        $ticket->update([
            'status' => 'in_progress'
        ]);

        return response()->json([
            'message' => 'Ticket aceptado',
            'ticket' => $ticket
        ]);
    }

    public function completeEntry(Ticket $ticket)
    {

        if ($ticket->type !== 'entry') {
            return response()->json(['message' => 'Ticket inválido'], 422);
        }

        if ($ticket->status !== 'in_progress') {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        $details = $ticket->details;

        $total = $ticket->quantity;

        $summary = [
            'completed' => $details->where('status', 'completed')->count(),
            'pending'   => $details->where('status', 'pending')->count(),
        ];

        //completar todo
        if ($summary['pending'] > 0) {
            return response()->json([
                'message' => 'Aún hay cajas sin procesar',
            ], 422);
        }

        if ($summary['completed'] > $total) {
            return response()->json([
                'message' => 'Inconsistencia en el ticket'
            ], 422);
        }

        //pedido completo y marcar cajas como completadas
        if ($summary['completed'] === $total) {
            DB::transaction(function () use ($ticket, $details) {

                foreach ($details as $detail) {
                    $this->updatePackageStatusFromDetail($detail);
                }

                $ticket->update(['status' => 'completed']);
            });

            return response()->json([
                'message' => 'Entrada completada, coloca las cajas en su lugar correspondiente.',
            ]);
        }


        //habilitar boton para funcion de pedir aprobacion
        if ($summary['completed'] < $total) {
            return response()->json([
                'message' => 'No se completo el pedido, ¿confirmar entrada parcial?',
                'action_required' => 'confirm_partial',
                'allowed_actions' => ['confirm_partial', 'cancel'],
            ], 409);
        }

        return response()->json([
            'message' => 'Estado no válido',
            'summary' => $summary
        ], 422);
    }

    public function completeSale(Ticket $ticket)
    {

        if ($ticket->type !== 'sale') {
            return response()->json(['message' => 'Ticket inválido'], 422);
        }

        if ($ticket->status !== 'in_progress') {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        $details = $ticket->details;
        $total = $ticket->quantity;
        $bookId = $ticket->stockTransactions()->first()->book_id;
        $avaible_books = $this->getAvailablePackages($bookId);
        //return response()->json(['message' => $avaible_books]);
        $summary = [
            'completed' => $details->where('status', 'completed')->count(),
            'pending'   => $details->where('status', 'pending')->count(),
            'damaged'   => $details->where('status', 'damaged')->count(),
            'missing'   => $details->where('status', 'missing')->count(),
            'other'   => $details->where('status', 'other')->count(),
        ];

        //completar todo
        if ($summary['pending'] > 0) {
            return response()->json([
                'message' => 'Aún hay cajas sin procesar',
            ], 422);
        }

        if ($summary['completed'] > $total) {
            return response()->json([
                'message' => 'Inconsistencia en el ticket'
            ], 422);
        }

        //pedido completo y marcar cajas como vendidas
        if ($summary['completed'] === $total) {
            DB::transaction(function () use ($ticket, $details) {

                foreach ($details as $detail) {
                    $package = $detail->package;
                    if ($detail->status == "completed") {
                        $package->update(['status' => 'sold']);
                    }
                }
                $ticket->update(['status' => 'in_delivery']);
            });

            return response()->json([
                'message' => 'Lleva las cajas al vehiculo correspondiente.',
            ]);
        }

        //disponibles suficientes para acompletar
        if ($summary['completed'] < $total && $avaible_books >= ($total - $summary['completed'])) {
            return response()->json([
                'message' => 'Aun hay paquetes disponibles,agrega los faltantes de remplazo.',
                'action_required' => 'add_replacement',
                'allowed_actions' => ['add', 'cancel'],
            ], 409);
        }

        //habilitar boton para funcion de pedir aprobacion
        if ($summary['completed'] < $total && $avaible_books < ($total - $summary['completed'])) {
            return response()->json([
                'message' => 'No se acompleta el pedido y ya no hay paquetes de este libro disponibles, ¿Solicitar un envio parcial?',
                'action_required' => 'confirm_partial',
                'allowed_actions' => ['confirm_partial', 'cancel'],
            ], 409);
        }

        return response()->json([
            'message' => 'Estado no válido',
            'summary' => $summary
        ], 422);
    }

    public function completeChange(Ticket $ticket)
    {
        if ($ticket->type !== 'change') {
            return response()->json(['message' => 'Ticket inválido'], 422);
        }

        if ($ticket->status !== 'in_progress') {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        $details = $ticket->details;
        $total = $ticket->quantity;
        $summary = [
            'completed' => $details->where('status', 'completed')->count(),
            'pending'   => $details->where('status', 'pending')->count(),
        ];

        //completar todo
        if ($summary['pending'] > 0) {
            return response()->json([
                'message' => 'Aún hay cajas sin procesar',
            ], 422);
        }

        if ($summary['completed'] > $total) {
            return response()->json([
                'message' => 'Inconsistencia en el ticket'
            ], 422);
        }

        if ($summary['completed'] === $total) {
            DB::transaction(function () use ($ticket, $details) {

                foreach ($details as $detail) {
                    $package = $detail->package;
                    if ($detail->status == "completed") {
                        $package->update([
                            'status' => 'available',
                            'pallet_id' => $detail->moved_to_pallet
                        ]);
                    }
                }
                $ticket->update(['status' => 'completed']);
            });

            return response()->json([
                'message' => 'Lleva las cajas al pallet correspondiente.',
            ]);
        }

        return response()->json([
            'message' => 'Estado no válido',
            'summary' => $summary
        ], 422);
    }

    public function completeRemoved(Ticket $ticket)
    {
        $validStatuses = ['removed', 'damaged', 'partial_damaged'];

        if (!in_array($ticket->type, $validStatuses)) {
            return response()->json(['message' => 'Ticket inválido'], 422);
        }

        if ($ticket->status !== 'in_progress') {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        $details = $ticket->details;
        $total = $ticket->quantity;
        $summary = [
            'completed' => $details->where('status', 'completed')->count(),
            'pending'   => $details->where('status', 'pending')->count(),
        ];

        //completar todo
        if ($summary['pending'] > 0) {
            return response()->json([
                'message' => 'Aún hay cajas sin procesar',
            ], 422);
        }

        if ($summary['completed'] > $total) {
            return response()->json([
                'message' => 'Inconsistencia en el ticket'
            ], 422);
        }

        if ($summary['completed'] === $total) {
            DB::transaction(function () use ($ticket, $details) {

                foreach ($details as $detail) {
                    $package = $detail->package;
                    if ($detail->status == "completed") {
                        if ($ticket->type == "removed") {

                            $package->update(['status' => 'removed']);

                        } elseif ($ticket->type == "damaged") {

                            $package->update(['status' => 'damaged']);

                        } elseif ($ticket->type == "partial_damaged") {

                            $package->update(['status' => 'partial_damaged']);

                        }
                    }
                }
                $ticket->update(['status' => 'completed']);
            });

            return response()->json([
                'message' => 'Ya puedes llevar las cajas a la basura o seguir instrucciones del ticket.',
            ]);
        }

        return response()->json([
            'message' => 'Estado no válido',
            'summary' => $summary
        ], 422);
    }

    //pedir aprobacion del ticket
    public function confirmPartial(Ticket $ticket)
    {
        if ($ticket->status !== 'in_progress') {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        $ticket->update([
            'status' => 'pending_partially_completed'
        ]);

        return response()->json([
            'message' => 'Solicitud enviada.'
        ]);
    }

    //paso final de entrada parcial valido para entrada salida ya sea rechazo o aprobacion
    public function completePartialTicket(Ticket $ticket)
    {
        $validStatuses = ['approve_partially', 'refused_partially'];

        if (!in_array($ticket->status, $validStatuses)) {
            return response()->json(['message' => 'Ticket no editable'], 422);
        }

        $details = $ticket->details;
        if ($ticket->status === 'approve_partially') {
            if ($ticket->type == "entry") {
                DB::transaction(function () use ($details) {

                    foreach ($details as $detail) {
                        $package = $detail->package;

                        if ($detail->status == "completed") {
                            $package->update(['status' => 'available']);
                        } else {
                            $package->update(['status' => 'return']);
                        }
                    }
                });

                $ticket->update([
                    'status' => 'partially_completed'
                ]);

                return response()->json([
                    'message' => 'Ticket completado parcialmente, por favor mueve las cajas a su lugar correspondiene.'
                ]);
            } elseif ($ticket->type == "sale") {
                DB::transaction(function () use ($details) {

                    foreach ($details as $detail) {
                        $package = $detail->package;

                        if ($detail->status == "completed") {
                            $package->update(['status' => 'sold']);
                        }
                    }
                });

                $ticket->update([
                    'status' => 'in_delivery'
                ]);

                return response()->json([
                    'message' => 'Lleva las cajas al vehiculo correspondiente.'
                ]);
            }
        } elseif ($ticket->status === 'refused_partially') {
            if ($ticket->type == "entry") {
                DB::transaction(function () use ($details) {

                    foreach ($details as $detail) {
                        $package = $detail->package;
                        $package->update(['status' => 'return']);
                    }
                });

                $ticket->update([
                    'status' => 'return'
                ]);

                return response()->json([
                    'message' => 'Ticket completado como devolución, por favor mueve las cajas para devolverlas.'
                ]);
            } elseif ($ticket->type == "sale") {
                DB::transaction(function () use ($details) {

                    foreach ($details as $detail) {
                        $package = $detail->package;

                        if ($detail->status == "completed") {
                            $package->update(['status' => 'available']);
                        }
                    }
                });

                $ticket->update([
                    'status' => 'cancelled'
                ]);

                return response()->json([
                    'message' => 'Ticket completado cancelado, por favor devuelve las cajas a su lugar correspondiente.'
                ]);
            }
        }


        return response()->json([
            'message' => 'Este ticket no pertenece a este flujo.'
        ]);
    }

    private function updatePackageStatusFromDetail(TicketDetail $detail)
    {
        $package = $detail->package;

        switch ($detail->status) {
            case 'completed':
                $package->update(['status' => 'available']);
                break;

                // case 'damaged':
                //     $package->update(['status' => 'damaged']);
                //     break;

                // case 'missing':
                //     $package->update(['status' => 'missing']);
                //     break;

                // case 'other':
                //     $package->update(['status' => 'other']);
                //     break;
        }
    }

    private function getAvailablePackages(int $bookId): int
    {
        return Package::where('book_id', $bookId)
            ->where('status', 'available')
            ->count();
    }
}
