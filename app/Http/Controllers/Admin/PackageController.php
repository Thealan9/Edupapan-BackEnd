<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Package;
use App\Models\Pallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index()
    {
        return Package::with([
            'pallet' => function($query) {$query->withTrashed()->select('id', 'pallet_code');},
            'book' => function($query) {$query->withTrashed()->select('id', 'title');}])
            ->get();
    }

    public function show(Package $package)
    {
        $pallets = Pallet::whereIn('status', ['open', 'empty'])
        ->withCount(['packages' => function ($query) {
            $query->whereIn('status', ['pending', 'available', 'reserved']);
        }])->get()
        ->map(function ($pallet) {
            $pallet->remaining_capacity = $pallet->max_packages_capacity - $pallet->packages_count;
            return $pallet;
        });
        $books = Book::pluck('id', 'title');

        return response()->json([
            'package' =>$package,
            'pallets' =>$pallets,
            'books' =>$books
            ]);
    }

    public function update(Request $request, Package $package)
    {
        $data = $request->validate([
            'batch_number'  => ['required', 'string', Rule::unique('packages', 'batch_number')->ignore($package->id),],
            'book_id'     => ['required', 'exists:books,id'],
            'book_quantity' => ['required', 'integer', 'min:1'],
            'pallet_id' => ['required', 'exists:pallets,id'],
        ]);

        $package->update($data);

        return response()->json([
            'message' => 'Paquete actualizado.',
        ]);
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return response()->json([
            'message' => 'Paquete eliminado.'
        ]);
    }

}
