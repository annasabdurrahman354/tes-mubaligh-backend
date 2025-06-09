<x-filament-panels::page>
    <div>
        <form wire:submit="generatePengumuman" class="fi-form">
            {{ $this->form }}
        </form>
        @if($pengumumanPerKelompok != [] && $pengumumanPerKelompok != null)
            <x-filament::section class="mt-6 overflow-auto" wire:loading.remove>
                <x-slot name="heading">
                    Pengumuman Hasil Tes
                </x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button wire:click="printPengumuman" id="button-print" color="primary" wire:loading.remove>
                        Print
                    </x-filament::button>
                    <x-filament::button color="primary" disabled wire:loading>
                        Loading...
                    </x-filament::button>
                </x-slot>

                <div id="view-print" style="background-color: white; color: black">
                    @if (!empty($pengumumanPerKelompok))
                        @foreach ($pengumumanPerKelompok as $kelompok => $santriKelompok)
                            <div style="page-break-after: always;">
                                <table style="width: 100%; border-collapse: separate; border-spacing: 1px;">
                                    @foreach ($santriKelompok as $index => $santriItem)
                                        @if ($index % 2 === 0)
                                            <tr>
                                                @endif

                                                <td style="width: 50%; padding: 15px 10px; vertical-align: top; border: 1px solid black !important; background-color: #f9f9f9; text-align: left; page-break-inside: avoid; break-inside: avoid; font-size: 14px;">
                                                    <div>
                                                        <div style="display: flex; align-items: flex-start; position: relative;">
                                                            <div style="flex: 1; color: black; padding-right: 60px; line-height: 1.4em; height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; text-overflow: ellipsis;">
                                                                <strong>Nama:</strong> {{ $santriItem['nama_lengkap'] }}
                                                            </div>
                                                            <div style="position: absolute; right: 0; font-weight: bold; background-color: #ddd; padding: 5px; width: 30px; text-align: center; color: black; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                                                {{ $santriItem['jenis_kelamin'] }}
                                                            </div>
                                                        </div>
                                                        <div style="color: black"><strong>Kelompok:</strong> {{ $santriItem['kelompok'] }}{{ $santriItem['nomor_cocard'] }} </div>
                                                        <div style="color: black"><strong>Alamat:</strong> {{ $santriItem['daerah_sambung'] }}</div>
                                                        <div style="color: black; line-height: 1.4em; height: 2.8em; overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; text-overflow: ellipsis;">
                                                            <strong>Pondok:</strong> {{ $santriItem['ponpes'] }} ({{ $santriItem['daerah_ponpes'] }})
                                                        </div>
                                                        <p style="margin-top: 10px; color: black">
                                                            @if ($santriItem['status'] === 'Lulus')
                                                                Dinyatakan <strong>Lulus</strong> Tes Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }} Dengan <strong>Nilai Akademik {{ $santriItem['nilai_akademik'] }}</strong>
                                                            @elseif ($santriItem['status'] === 'Tidak Lulus Akademik')
                                                                Dinyatakan <strong>Tidak Lulus</strong> Tes Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }} Dengan <strong>Nilai Akademik {{ $santriItem['nilai_akademik'] }}</strong>
                                                            @elseif ($santriItem['status'] === 'Tidak Lulus Akhlak')
                                                                Dinyatakan <strong>Tidak Lulus</strong> Tes Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }} Karena <strong>Kurang Dalam Ketertiban</strong>
                                                            @else
                                                                Dinyatakan <strong>{{ $santriItem['status'] }}</strong> Tes Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                </td>
                                                @if ($index % 2 !== 0)
                                            </tr>
                                            @endif
                                            @endforeach

                                            @if (count($santriKelompok) % 2 !== 0)
                                                </tr>
                                        @endif
                                </table>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning" role="alert">
                            Tidak ada data santri ditemukan.
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @elseif($pengumumanPerKelompok == [] && $pengumumanPerKelompok != null)
            <div class="mt-6" wire:loading.remove>
                Tidak ada data peserta!
            </div>
        @endif
    </div>
</x-filament-panels::page>
