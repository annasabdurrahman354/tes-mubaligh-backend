<x-filament-panels::page>
    <div>
        <form wire:submit="generatePengumuman" class="fi-form">
            {{ $this->form }}
        </form>
        @if(!empty($pengumuman))
            <x-filament::section class="mt-6 overflow-auto" wire:loading.remove>
                <x-slot name="heading">
                    Pengumuman Hasil Tes
                </x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button id="button-print" color="success" wire:loading.remove>
                        Print
                    </x-filament::button>
                    <x-filament::button color="secondary" disabled wire:loading>
                        Loading...
                    </x-filament::button>
                </x-slot>

                <div id="view-print">
                    <div>
                        @if (!empty($pengumuman))
                            <table>
                                @foreach ($pengumuman as $index => $santriItem)
                                    @if ($index % 2 === 0)
                                        <tr>
                                            @endif

                                            <td>
                                                <div class="santri-item">
                                                    <div class="gender-box text">
                                                        {{ $santriItem['jenis_kelamin'] }}
                                                    </div>
                                                    <div class="text"><strong>Nama:</strong> {{ $santriItem['nama_lengkap'] }}</div>
                                                    <div class="text"><strong>Kelompok:</strong> {{ $santriItem['kelompok'] }}{{ $santriItem['nomor_cocard'] }}</div>
                                                    <div class="text"><strong>Alamat:</strong> {{ $santriItem['daerah_sambung'] }}</div>
                                                    <div class="text"><strong>Pondok:</strong> {{ $santriItem['ponpes'] }}</div>
                                                    <div class="text"><strong>Daerah Pondok:</strong> {{ $santriItem['daerah_ponpes'] }}</div>
                                                    <div style="color: white">​</div>
                                                    <p class="text">
                                                        @if ($santriItem['status'] === 'Lulus')
                                                            Dinyatakan <strong>Lulus</strong> Tes Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }} Dengan <strong>Nilai Akademik {{ $santriItem['nilai_akademik'] }}</strong>
                                                        @elseif ($santriItem['status'] === 'Tidak Lulus Akademik')
                                                            Dinyatakan <strong>Tidak Lulus Tes</strong> Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }} Dengan <strong>Nilai Akademik {{ $santriItem['nilai_akademik'] }}</strong>
                                                        @elseif ($santriItem['status'] === 'Tidak Lulus Akhlak')
                                                            Dinyatakan <strong>Tidak Lulus Tes</strong> Kediri Periode {{ $santriItem['periode_bulan'] }} {{ $santriItem['periode_tahun'] }} Karena <strong>Kurang Dalam Ketertiban</strong>
                                                        @else
                                                            <strong>Status Tes {{$santriItem['status']}} </strong>
                                                        @endif
                                                    </p>
                                                </div>
                                            </td>

                                            @if ($index % 2 !== 0)
                                        </tr>
                                        @endif
                                        @endforeach

                                        @if (count($pengumuman) % 2 !== 0)
                                            </tr>
                                    @endif
                            </table>
                        @else
                            <div class="alert alert-warning" role="alert">
                                Tidak ada data santri ditemukan.
                            </div>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @else
            <div class="mt-6" wire:loading.remove>
                Tidak ada data peserta!
            </div>
        @endif
    </div>
</x-filament-panels::page>

@push('styles')
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            border: 1px solid black !important;
            padding: 10px;
        }

        td {
            width: 50%;
            vertical-align: top;
            background-color: white;
            text-align: left;
            page-break-inside: avoid; /* Prevents splitting within a cell */
            break-inside: avoid;
        }

        tr {
            page-break-inside: avoid; /* Ensures the row does not break across pages */
            break-inside: avoid;
        }

        .santri-item {
            position: relative;
            padding: 10px;
            background-color: white;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .gender-box {
            position: absolute;
            top: 5px;
            right: 5px;
            font-weight: bold;
            background-color: #ddd;
            padding: 5px 10px;
            text-align: center;
        }

        .text {
            color: black !important;
        }

        /* Ensure the styles also work for printing */
        @media print {
            td, th {
                border: 1px solid black !important;
                padding: 10px;
            }

            td, tr, .santri-item {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .gender-box {
                background-color: #ddd !important; /* Ensure background color is applied */
                -webkit-print-color-adjust: exact; /* Safari/Chrome */
                print-color-adjust: exact; /* Standard */
            }

            .text {
                color: black !important;
            }
        }
    </style>
@endpush

<script>
    document.getElementById("button-print").onclick = function() {
        document.body.innerHTML = document.getElementById('view-print').innerHTML;
        window.print();
        location.reload();
    };
</script>
