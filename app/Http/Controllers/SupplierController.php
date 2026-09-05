<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query()->withCount(['stockMovements']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->orderBy('name')->paginate(min($request->integer('per_page', 50), 200)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ], [], [
            'name' => 'Nama',
            'phone' => 'Telepon',
            'email' => 'Email',
            'address' => 'Alamat',
            'notes' => 'Catatan',
            'is_active' => 'Status aktif',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ], [], [
            'name' => 'Nama',
            'phone' => 'Telepon',
            'email' => 'Email',
            'address' => 'Alamat',
            'notes' => 'Catatan',
            'is_active' => 'Status aktif',
        ]);

        $supplier->update($validated);

        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->stockMovements()->count() > 0) {
            return response()->json([
                'message' => 'Tidak bisa menghapus — supplier memiliki riwayat stok.',
            ], 422);
        }

        $supplier->delete();

        return response()->json(['message' => 'Supplier berhasil dihapus.']);
    }
}