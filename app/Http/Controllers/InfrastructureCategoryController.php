<?php

namespace App\Http\Controllers;

use App\Http\Requests\InfrastructureCategoryRequest;
use App\Models\InfrastructureCategory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InfrastructureCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ], [
            'q.string' => 'Pencarian kategori harus berupa teks.',
            'q.max' => 'Pencarian kategori maksimal 255 karakter.',
        ]);
        $search = trim($validated['q'] ?? '');

        $categories = InfrastructureCategory::query()
            ->withCount('deviceTypes')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('nama_kategori', 'like', '%'.$search.'%');
            })
            ->orderBy('nama_kategori')
            ->orderBy('id_kategori')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(InfrastructureCategoryRequest $request): RedirectResponse
    {
        try {
            InfrastructureCategory::create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'nama_kategori' => 'Nama kategori sudah digunakan.',
            ]);
        }

        return to_route('categories.index')->with('success', 'Kategori infrastruktur berhasil ditambahkan.');
    }

    public function edit(InfrastructureCategory $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(InfrastructureCategoryRequest $request, InfrastructureCategory $category): RedirectResponse
    {
        try {
            $category->update($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'nama_kategori' => 'Nama kategori sudah digunakan.',
            ]);
        }

        return to_route('categories.index')->with('success', 'Kategori infrastruktur berhasil diperbarui.');
    }

    public function destroy(InfrastructureCategory $category): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($category): bool {
            // Kunci induk agar insert FK bersamaan menunggu sebelum pemeriksaan relasi.
            $category = InfrastructureCategory::query()->lockForUpdate()->findOrFail($category->getKey());

            if ($category->deviceTypes()->exists()) {
                return false;
            }

            return (bool) $category->delete();
        });

        if (! $deleted) {
            return to_route('categories.index')->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh jenis perangkat.');
        }

        return to_route('categories.index')->with('success', 'Kategori infrastruktur berhasil dihapus.');
    }
}
