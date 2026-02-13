<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Package;
use App\Models\Pallet;
use App\Models\StockTransaction;
use App\Models\Ticket;
use App\Models\TicketDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index()
    {
        $workers = User::where('role','warehouseman')->pluck('id', 'name');
        $pallets = Pallet::whereIn('status', ['open', 'empty'])
        ->withCount(['packages' => function ($query) {
            $query->whereIn('status', ['pending', 'available', 'reserved']);
        }])->get()
        ->map(function ($pallet) {
            $pallet->remaining_capacity = $pallet->max_packages_capacity - $pallet->packages_count;
            return $pallet;
        });
        $books = Book::pluck('id', 'title');
        $packages = Package::whereIn('status', ['available'])
        ->select('id', 'batch_number', 'pallet_id', 'book_id')
        ->get()
        ->groupBy('book_id');
        return response()->json(
            ['workers' => $workers,
            'pallets' => $pallets,
            'books' => $books,
            'packages' => $packages
            ]);
    }

    public function getRequest(){
        $res = Ticket::where('status','pending_partially_completed')
        ->whereIn('type',['entry','sale'])
        ->select('id','type',)
        ->get();

        return response()->json($res);
    }
    public function showRequest(Ticket $ticket){
        $ticket->load('details.package');


        $buenEstadoCount = $ticket->details->where('status', 'completed')->count();

        $faltantes = $ticket->quantity - $buenEstadoCount;

        $ticket->buen_estado_count = $buenEstadoCount;
        $ticket->faltantes = $faltantes;

        return response()->json($ticket);
    }

    public function getSolution(){
        $res = TicketDetail::whereIn('status',['damaged', 'missing', 'other'])
        ->select('id', 'status', 'package_id')
        ->with('package:id,batch_number')
        ->get();

        return response()->json($res);
    }
    public function showSolution(TicketDetail $detail){
        $detail->load(['package' => function($query) {
            $query->select('id', 'batch_number');
        }]);
        $workers = User::where('role','warehouseman')->pluck('id', 'name');

        return response()->json([
            'detail' =>$detail,
            'workers' =>$workers]);
    }

    public function createEntry(Request $request)
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'book_id'     => ['required', 'exists:books,id'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'description'     => ['nullable', 'string'],
            'packages'    => ['required', 'array', 'min:1'],

            'packages.*.batch_number'  => ['required', 'string'],
            'packages.*.book_quantity' => ['required', 'integer', 'min:1'],
            'packages.*.moved_to_pallet' => ['required', 'exists:pallets,id'],
        ]);

        DB::transaction(function () use ($data, $request, &$ticket) {
            $ticket = Ticket::create([
                'type'        => 'entry',
                'status'      => 'pending',
                'assigned_to' => $data['assigned_to'],
                'quantity'    => $data['quantity'],
                'description'     => $data['description'] ?? null,
            ]);

            StockTransaction::create([
                'book_id'   => $data['book_id'],
                'user_id'   => $request->user()->id,
                'ticket_id' => $ticket->id,
            ]);

            $this->processTicketDetails($ticket, $data['packages'], $data['book_id']);
        });
        return response()->json([
            'message' => 'Entrada procesada correctamente.',
            'ticket'  => $ticket
        ], 201);
    }
    public function createSale(Request $request)
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'book_id'     => ['required', 'exists:books,id'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'description'     => ['nullable', 'string'],
            'packages'    => ['required', 'array', 'min:1'],

            'packages.*.package_id' => ['required', 'exists:packages,id'],
        ]);

        $validStatuses = ['available'];
        foreach ($data['packages'] as $pkg) {
            $package = Package::findOrFail($pkg['package_id']);
            if (!in_array($package->status,$validStatuses)) {
                return response()->json([
                    'message' => 'Este paquete no se puede asignar al ticket, revisa status',
                    'motivo'  => $package
                ], 422);
            }
        }

        DB::transaction(function () use ($data, $request, &$ticket) {
            $ticket = Ticket::create([
                'type'        => 'sale',
                'status'      => 'pending',
                'assigned_to' => $data['assigned_to'],
                'quantity'    => $data['quantity'],
                'description'     => $data['description'] ?? null,
            ]);

            StockTransaction::create([
                'book_id'   => $data['book_id'],
                'user_id'   => $request->user()->id,
                'ticket_id' => $ticket->id,
            ]);

            $this->processTicketDetails($ticket, $data['packages'], $data['book_id']);
        });
        return response()->json([
            'message' => 'Venta procesada correctamente.',
            'ticket'  => $ticket
        ], 201);
    }
    public function createRemoved(Request $request)
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'book_id'     => ['required', 'exists:books,id'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'description'     => ['nullable', 'string'],
            'packages'    => ['required', 'array', 'min:1'],

            'packages.*.package_id' => ['required', 'exists:packages,id', 'distinct'],
        ]);

        $validStatuses = ['available','reserved','other'];
        foreach ($data['packages'] as $pkg) {
            $package = Package::findOrFail($pkg['package_id']);
            if (!in_array($package->status,$validStatuses)) {
                return response()->json([
                    'message' => 'Este paquete no se puede asignar al ticket, revisa status',
                    'motivo'  => $package
                ], 422);
            }
        }

        DB::transaction(function () use ($data, $request, &$ticket) {
            $ticket = Ticket::create([
                'type'        => 'removed',
                'status'      => 'pending',
                'assigned_to' => $data['assigned_to'],
                'quantity'    => $data['quantity'],
                'description'     => $data['description'] ?? null,
            ]);

            StockTransaction::create([
                'book_id'   => $data['book_id'],
                'user_id'   => $request->user()->id,
                'ticket_id' => $ticket->id,
            ]);

            $this->processTicketDetails($ticket, $data['packages'], $data['book_id']);
        });
        return response()->json([
            'message' => 'Retiro procesado correctamente.',
            'ticket'  => $ticket
        ], 201);
    }
    public function createChange(Request $request)
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'book_id'     => ['required', 'exists:books,id'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'description'     => ['nullable', 'string'],
            'packages'    => ['required', 'array', 'min:1'],


            'packages.*.package_id' => ['required', 'exists:packages,id', 'distinct'],
            'packages.*.moved_to_pallet' => ['required', 'exists:pallets,id'],
        ]);

        $validStatuses = ['available'];
        foreach ($data['packages'] as $pkg) {
            $package = Package::findOrFail($pkg['package_id']);
            if (!in_array($package->status,$validStatuses)) {
                return response()->json([
                    'message' => 'Este paquete no se puede asignar al ticket, revisa status',
                    'motivo'  => $package
                ], 422);
            }
        }

        DB::transaction(function () use ($data, $request, &$ticket) {
            $ticket = Ticket::create([
                'type'        => 'change',
                'status'      => 'pending',
                'assigned_to' => $data['assigned_to'],
                'quantity'    => $data['quantity'],
                'description'     => $data['description'] ?? null,
            ]);

            StockTransaction::create([
                'book_id'   => $data['book_id'],
                'user_id'   => $request->user()->id,
                'ticket_id' => $ticket->id,
            ]);

            $this->processTicketDetails($ticket, $data['packages'], $data['book_id']);
        });
        return response()->json([
            'message' => 'Cambio procesada correctamente.',
            'ticket'  => $ticket
        ], 201);
    }

    private function processTicketDetails(Ticket $ticket, array $packages, int $bookId)
    {

        $created = 0;

        foreach ($packages as $pkg) {

            if ($created >= $ticket->quantity) {
                break;
            }
            if ($ticket->type === 'entry') {
                $package = Package::create([
                    'batch_number'  => $pkg['batch_number'],
                    'book_id'       => $bookId,
                    'pallet_id'     => $pkg['moved_to_pallet'],
                    'book_quantity' => $pkg['book_quantity'],
                    'status'        => 'pending',
                ]);
            } else {
                $package = Package::findOrFail($pkg['package_id']);
                $package->update(['status' => 'reserved']);
            }

            TicketDetail::create([
                'ticket_id' => $ticket->id,
                'package_id' => $package->id,
                'status'    => 'pending',
                'moved_to_pallet' => in_array($ticket->type, ['entry', 'change']) ? $pkg['moved_to_pallet'] : null,
                'description' => $pkg['description'] ?? null,
            ]);

            $created++;
        }
    }



    //aprovar o rechazar soli parcial entrada y salida

    public function ApprovePartial(Ticket $ticket)
    {
        if ($ticket->status !== 'pending_partially_completed') {
            return response()->json(['message' => 'Ticket no valido'], 422);
        }

        if ($ticket->type == 'entry' || $ticket->type == 'sale') {
            $ticket->update([
                'status' => 'approve_partially'
            ]);
        }

        return response()->json([
            'message' => ' Solicitud aprobada'
        ]);
    }


    public function RejectPartial(Ticket $ticket)
    {
        if ($ticket->status !== 'pending_partially_completed') {
            return response()->json(['message' => 'Ticket no valido'], 422);
        }

        if ($ticket->type == 'entry' || $ticket->type == 'sale') {
            $ticket->update([
                'status' => 'refused_partially'
            ]);
        }
        return response()->json([
            'message' => 'Solicitud rechazada'
        ]);
    }
}
