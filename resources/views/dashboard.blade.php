@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
            
            <!-- 4 Top Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-7">
                <!-- Card 1: Laporan Masuk Hari Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#2563eb]">
                    <p class="text-xs font-semibold text-slate-500">Laporan masuk hari ini</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $todayReportsCount }}</p>
                </div>

                <!-- Card 2: Menunggu Verifikasi -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#475569]">
                    <p class="text-xs font-semibold text-slate-500">Menunggu verifikasi</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $waitingVerificationCount }}</p>
                </div>

                <!-- Card 3: Dalam Perbaikan -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#f59e0b]">
                    <p class="text-xs font-semibold text-slate-500">Dalam perbaikan</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $inRepairCount }}</p>
                </div>

                <!-- Card 4: Selesai Bulan Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 border-t-4 border-t-[#10b981]">
                    <p class="text-xs font-semibold text-slate-500">Selesai bulan ini</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ $doneThisMonthCount }}</p>
                </div>
            </div>

            <!-- Lower 2 Columns Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-7 items-start">

                <!-- LEFT COLUMN: Laporan Terbaru (2 spans) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                    <h3 class="text-base font-bold text-slate-900 mb-5">Laporan Terbaru</h3>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                                    <th class="pb-3 font-semibold">NO. LAPORAN</th>
                                    <th class="pb-3 font-semibold">PERANGKAT</th>
                                    <th class="pb-3 font-semibold">LOKASI</th>
                                    <th class="pb-3 font-semibold">STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($recentReports as $report)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-3.5 font-medium text-slate-800">{{ $report['nomor'] }}</td>
                                        <td class="py-3.5 text-slate-800">{{ $report['perangkat'] }}</td>
                                        <td class="py-3.5 text-slate-600">{{ $report['lokasi'] }}</td>
                                        <td class="py-3.5">
                                            <span class="inline-block px-3 py-0.5 rounded-full text-[11px] font-semibold {{ $report['badge_class'] }}">
                                                {{ $report['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Link -->
                    <div class="mt-6 pt-2">
                        <a href="#" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                            Lihat semua laporan &rarr;
                        </a>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Charts & Top Damaged (1 span) -->
                <div class="space-y-7">
                    
                    <!-- Card: Laporan per Gedung -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                        <h3 class="text-sm font-bold text-slate-900 mb-5">Laporan per Gedung (Okt 2026)</h3>
                        <div class="space-y-4">
                            @foreach ($buildingStats as $building)
                                <div class="flex items-center justify-between gap-4 text-xs">
                                    <span class="w-28 text-slate-600 truncate">{{ $building['nama'] }}</span>
                                    <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-[#2563eb] h-2.5 rounded-full" style="width: {{ $building['width'] }};"></div>
                                    </div>
                                    <span class="w-5 text-right font-bold text-slate-800">{{ $building['jumlah'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Card: Perangkat Sering Rusak -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
                        <h3 class="text-sm font-bold text-slate-900 mb-5">Perangkat Sering Rusak</h3>
                        <div class="space-y-3.5">
                            @foreach ($frequentDamagedDevices as $device)
                                <div class="flex items-center justify-between text-xs py-1">
                                    <span class="text-slate-700">{{ $device['nama'] }}</span>
                                    <span class="font-bold text-slate-900">{{ $device['jumlah'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

            </div>


@endsection
