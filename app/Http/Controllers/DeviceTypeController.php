<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Models\InfrastructureCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTypeController extends Controller
{
    public function index()
    {
        $types = DeviceType::with('category')->orderBy('nama_jenis')->get();

        return view('device-types.index', compact('types'));
    }

    public function create()
    {
        $categories = InfrastructureCategory::orderBy('nama_kategori')->get();

        return view('device-types.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_kategori' => ['required', 'exists:infrastructure_categories,id_kategori'],
            'nama_jenis'  => ['required', 'string', 'max:100', 'unique:device_types,nama_jenis'],
        ], [
            'id_kategori.required' => 'Kategori infrastruktur wajib dipilih.',
            'id_kategori.exists'    => 'Kategori yang dipilih tidak valid.',
            'nama_jenis.required'   => 'Nama jenis perangkat wajib diisi.',
            'nama_jenis.unique'     => 'Nama jenis perangkat sudah terdaftar.',
        ]);

        DeviceType::create($validated);

        return redirect()->route('device-types.index')
            ->with('success', 'Jenis perangkat berhasil ditambahkan.');
    }

    public function edit(DeviceType $deviceType)
    {
        $categories = InfrastructureCategory::orderBy('nama_kategori')->get();

        return view('device-types.edit', compact('deviceType', 'categories'));
    }

    public function update(Request $request, DeviceType $deviceType)
    {
        $validated = $request->validate([
            'id_kategori' => ['required', 'exists:infrastructure_categories,id_kategori'],
            'nama_jenis'  => [
                'required', 'string', 'max:100',
                Rule::unique('device_types', 'nama_jenis')->ignore($deviceType->id_jenis, 'id_jenis'),
            ],
        ], [
            'id_kategori.required' => 'Kategori infrastruktur wajib dipilih.',
            'id_kategori.exists'    => 'Kategori yang dipilih tidak valid.',
            'nama_jenis.required'   => 'Nama jenis perangkat wajib diisi.',
            'nama_jenis.unique'     => 'Nama jenis perangkat sudah terdaftar.',
        ]);

        $deviceType->update($validated);

        return redirect()->route('device-types.index')
            ->with('success', 'Jenis perangkat berhasil diperbarui.');
    }

    public function destroy(DeviceType $deviceType)
    {
        if ($deviceType->devices()->exists()) {
            return redirect()->route('device-types.index')
                ->with('error', 'Jenis perangkat tidak dapat dihapus karena masih digunakan oleh data perangkat.');
        }

        $deviceType->delete();

        return redirect()->route('device-types.index')
            ->with('success', 'Jenis perangkat berhasil dihapus.');
    }
}
