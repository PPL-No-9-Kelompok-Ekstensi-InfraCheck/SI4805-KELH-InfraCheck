<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomRequest;
use App\Models\Building;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $search = trim($validated['q'] ?? '');

        $rooms = Room::query()
            ->with('building')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_ruangan', 'like', '%'.$search.'%')
                        ->orWhere('nama_ruangan', 'like', '%'.$search.'%')
                        ->orWhereHas('building', function ($query) use ($search) {
                            $query->where('kode_gedung', 'like', '%'.$search.'%')
                                ->orWhere('nama_gedung', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('kode_ruangan')
            ->orderBy('id_ruangan')
            ->paginate(10)
            ->withQueryString();

        return view('rooms.index', compact('rooms', 'search'));
    }

    public function create(): View
    {
        $buildings = Building::query()->orderBy('kode_gedung')->orderBy('id_gedung')->get();

        return view('rooms.create', compact('buildings'));
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        Room::create($request->validated());

        return to_route('rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function edit(Room $room): View
    {
        $buildings = Building::query()->orderBy('kode_gedung')->orderBy('id_gedung')->get();

        return view('rooms.edit', compact('room', 'buildings'));
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return to_route('rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($room): bool {
            // Lock the parent before checking references so concurrent FK inserts must wait.
            $room = Room::query()->lockForUpdate()->findOrFail($room->getKey());

            if ($room->devices()->exists() || $room->reports()->exists()) {
                return false;
            }

            return (bool) $room->delete();
        });

        if (! $deleted) {
            return to_route('rooms.index')->with('error', 'Ruangan tidak dapat dihapus karena masih direferensikan perangkat atau laporan.');
        }

        return to_route('rooms.index')->with('success', 'Ruangan berhasil dihapus.');
    }
}
