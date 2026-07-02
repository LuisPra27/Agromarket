<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with('categoria')
            ->where('stock', '>', 0);

        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->has('buscar')) {
            $query->where('nombre', 'ilike', '%' . $request->buscar . '%');
        }

        $productos = $query->orderBy('nombre')->get();

        return response()->json($productos);
    }

    public function show(Producto $producto): JsonResponse
    {
        if ($producto->stock <= 0) {
            return response()->json(['message' => 'Producto no disponible.'], 404);
        }

        return response()->json($producto->load('categoria'));
    }
}
