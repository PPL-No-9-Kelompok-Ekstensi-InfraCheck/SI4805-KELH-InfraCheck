<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuildingRequest;
use App\Models\Building;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $search = trim($validated['q'] ?? '');

        $buildings = Building::query()
            ->withCount('rooms')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_gedung', 'like', '%'.$search.'%')
                        ->orWhere('nama_gedung', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('kode_gedung')
            ->orderBy('id_gedung')
            ->paginate(10)
            ->withQueryString();

        return view('buildings.index', compact('buildings', 'search'));
    }

    public function create(): View
    {
        return view('buildings.create');
    }

    public function store(BuildingRequest $request): RedirectResponse
    {
        Building::create($request->validated());

        return to_route('buildings.index')->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function edit(Building $building): View
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(BuildingRequest $request, Building $building): RedirectResponse
    {
        $building->update($request->validated());

        return to_route('buildings.index')->with('success', 'Gedung berhasil diperbarui.');
    }

    public function destroy(Building $building): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($building): bool {
            // Lock the parent before checking rooms so a concurrent FK insert must wait.
            $building = Building::query()->lockForUpdate()->findOrFail($building->getKey());

            if ($building->rooms()->exists()) {
                return false;
            }

            return (bool) $building->delete();
        });

        if (! $deleted) {
            return to_route('buildings.index')->with('error', 'Gedung tidak dapat dihapus karena masih memiliki ruangan.');
        }

        return to_route('buildings.index')->with('success', 'Gedung berhasil dihapus.');
    }
}
