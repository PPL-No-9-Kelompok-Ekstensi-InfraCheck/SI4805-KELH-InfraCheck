<?php

namespace App\Http\Controllers;

use App\Enums\Kondisi;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with(['deviceType.category', 'room.building'])
            ->orderBy('nama_perangkat')
            ->paginate(15);

        return view('devices.index', compact('devices'));
    }

    public function create()
    {
        return view('devices.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        Device::create($validated);

        return redirect()->route('devices.index')
            ->with('success', 'Perangkat berhasil ditambahkan.');
    }

    public function edit(Device $device)
    {
        return view('devices.edit', array_merge($this->formData(), compact('device')));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate($this->rules($device), $this->messages());

        $device->update($validated);

        return redirect()->route('devices.index')
            ->with('success', 'Perangkat berhasil diperbarui.');
    }

    public function destroy(Device $device)
    {
        if ($device->reports()->exists()) {
            return redirect()->route('devices.index')
                ->with('error', 'Perangkat tidak dapat dihapus karena masih digunakan oleh data laporan kerusakan.');
        }

        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Perangkat berhasil dihapus.');
    }

    private function formData(): array
    {
        return [
            'types'   => DeviceType::with('category')->orderBy('nama_jenis')->get(),
            'rooms'   => Room::with('building')->orderBy('nama_ruangan')->get(),
            'kondisi' => Kondisi::cases(),
        ];
    }

    private function rules(?Device $device = null): array
    {
        return [
            'id_jenis'       => ['required', 'exists:device_types,id_jenis'],
            'id_ruangan'     => ['required', 'exists:rooms,id_ruangan'],
            'kode_aset'      => [
                'required', 'string', 'max:50',
                Rule::unique('devices', 'kode_aset')->ignore($device?->id_perangkat, 'id_perangkat'),
            ],
            'nama_perangkat' => ['required', 'string', 'max:150'],
            'kondisi'        => ['required', Rule::enum(Kondisi::class)],
        ];
    }

    private function messages(): array
    {
        return [
            'id_jenis.required'       => 'Jenis perangkat wajib dipilih.',
            'id_jenis.exists'         => 'Jenis perangkat yang dipilih tidak valid.',
            'id_ruangan.required'     => 'Ruangan wajib dipilih.',
            'id_ruangan.exists'       => 'Ruangan yang dipilih tidak valid.',
            'kode_aset.required'      => 'Kode inventaris wajib diisi.',
            'kode_aset.unique'        => 'Kode inventaris sudah digunakan perangkat lain.',
            'nama_perangkat.required' => 'Nama perangkat wajib diisi.',
            'kondisi.required'        => 'Kondisi perangkat wajib dipilih.',
            'kondisi.enum'            => 'Kondisi perangkat tidak valid.',
        ];
    }
}
