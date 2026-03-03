<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index()
    {
        return Book::get();
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'isbn'     => ['required', 'string', 'max:13', 'min:13', 'unique:books,isbn'],
            'level'    => ['required', 'in:A1,A2,B1,B2,C1,C2'],
            'price'    => ['required', 'numeric', 'min:0'],
            'supplier' => ['required', 'string', 'max:255'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'autor'       => ['required', 'string', 'max:255'],
            'active'      => ['boolean'],
            'pages'       => ['required', 'integer', 'min:1'],
            'year'        => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'edition'     => ['required', 'integer', 'min:1'],
            'format'      => ['required', 'in:Bolsillo,Tapa Blanda,Tapa Dura'],
            'size'        => ['required', 'string', 'max:50'],

        ]);

        $libro = Book::create($data);

        return response()->json([
            'message' => 'Libro insertado correctamente.',
            'local'   => $libro
        ], 201);
    }

    public function show(Book $libro)
    {
        return $libro;
    }

    public function update(Request $request, Book $libro)
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'isbn'     => [
                'required',
                'string',
                'max:13',
                'min:13',
                Rule::unique('books', 'isbn')->ignore($libro->id),
            ],
            'level'    => ['required', 'in:A1,A2,B1,B2,C1,C2'],
            'price'    => ['required', 'numeric', 'min:0'],
            'supplier' => ['required', 'string', 'max:255'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'autor'       => ['required', 'string', 'max:255'],
            'active'      => ['boolean'],
            'pages'       => ['required', 'integer', 'min:1'],
            'year'        => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'edition'     => ['required', 'integer', 'min:1'],
            'format'      => ['required', 'in:Bolsillo,Tapa Blanda,Tapa Dura'],
            'size'        => ['required', 'string', 'max:50'],
        ]);

        $libro->update($data);

        return response()->json([
            'message' => 'Libro actualizado.',
            'local' => $libro
        ]);
    }

    public function destroy(Book $libro)
    {
        $books = $libro->packages;
        $asig = $books->whereIn('status',['reserved','available','pending'])->count();

        if($asig > 0 ){
            return response()->json([
                'message' => 'No debe haber paquetes asignados a este libro para eliminar',
            ], 422);
        }

        $libro->delete();

        return response()->json([
            'message' => 'Libro eliminado.'
        ]);
    }
}
