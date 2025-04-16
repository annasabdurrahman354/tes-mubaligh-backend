<x-filament-panels::page>
    <div>
        <form wire:submit="generateBiodata" class="fi-form">
            {{ $this->form }}
        </form>
        @if($biodata != [] && $biodata != null)
            <x-filament::section class="mt-6 overflow-auto" wire:loading.remove>
                <x-slot name="heading">
                    Biodata Peserta Kertosono
                </x-slot>
                <x-slot name="headerEnd">
                    <x-filament::button wire:click="printBiodata" id="button-print" color="primary" wire:loading.remove>
                        Print
                    </x-filament::button>
                    <x-filament::button color="primary" disabled wire:loading>
                        Loading...
                    </x-filament::button>
                </x-slot>

                <div id="view-print">
                    <div>
                        @if (!empty($biodata))
                            <table>
                                @foreach ($biodata as $index => $santriItem)
                                    @if ($index % 2 === 0)
                                        <tr> {{-- Start a new row every 2 items --}}
                                            @endif

                                            <td>
                                                {{-- Start: Replaced content with detailed biodata --}}
                                                <div class="biodata-container"> {{-- Changed class from 'container' to avoid potential conflicts --}}
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Nama</div>
                                                        <div class="biodata-value-col">{{ $santriItem['nama_lengkap'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col header-extra">{{ $santriItem['jenis_kelamin'] ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Orang Tua</div>
                                                        <div class="biodata-value-col">{{ $santriItem['nama_ayah'] ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">TTL</div>
                                                        <div class="biodata-value-col">{{ $santriItem['tempat_lahir'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col">{{ $santriItem['tanggal_lahir'] ?? 'N/A' }}</div> {{-- Assuming DD-MM-YYYY format --}}
                                                    </div>
                                                    <div class="biodata-row address-row">
                                                        <div class="biodata-label-col">Alamat</div>
                                                        <div class="biodata-value-col address-value">
                                                            {{-- Use {!! !!} if alamat_lengkap contains HTML like <br> --}}
                                                            {!! $santriItem['alamat'] ?? 'N/A' !!}
                                                        </div>
                                                        <div class="biodata-extra-col"></div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Desa/Kel</div>
                                                        <div class="biodata-value-col">{{ $santriItem['desa_kel'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col">Kec. {{ $santriItem['kecamatan'] ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Kota/Kab</div>
                                                        <div class="biodata-value-col">{{ $santriItem['kota_kab'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col">Telp. {{ $santriItem['hp'] ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Daerah</div>
                                                        <div class="biodata-value-col">{{ $santriItem['daerah_sambung'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col">Kelp. {{ $santriItem['kelompok_sambung'] ?? 'N/A' }}</div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Pondok</div>
                                                        <div class="biodata-value-col">{{ $santriItem['ponpes'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col"></div>
                                                    </div>
                                                    <div class="biodata-row">
                                                        <div class="biodata-label-col">Pendidikan</div>
                                                        <div class="biodata-value-col">{{ $santriItem['pendidikan'] ?? 'N/A' }}</div>
                                                        <div class="biodata-extra-col">Status: {{ $santriItem['status_mondok'] ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            @if ($index % 2 !== 0)
                                        </tr> {{-- End the row after the second item --}}
                                        @endif
                                        @endforeach

                                        {{-- Close the row if the last row had only one item --}}
                                        @if (count($biodata) % 2 !== 0)
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
        @elseif($biodata == [] && $biodata != null)
            <div class="mt-6" wire:loading.remove>
                Tidak ada data peserta!
            </div>
        @endif
    </div>
</x-filament-panels::page>

@push('styles')
    <style>
        /* Styles for the overall table layout */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px; /* Add some space below the table */
        }

        td, th {
            border: 1px solid black !important;
            padding: 0; /* Remove padding from td itself, apply it to inner container */
            vertical-align: top;
            background-color: white;
            text-align: left;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        td {
            width: 50%; /* Each cell takes half the width */
        }

        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Styles for the detailed biodata content inside each cell */
        .biodata-container {
            font-family: Arial, sans-serif;
            color: black;
            line-height: 1.4;
            padding: 10px; /* Padding inside the container */
            margin: 5px; /* Optional: small margin inside the td border */
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
        }

        .biodata-row {
            display: flex;
            margin-bottom: 4px; /* Smaller space between rows */
            align-items: baseline;
            font-size: 0.9em; /* Slightly smaller font size */
        }

        .biodata-label-col {
            flex: 0 0 90px; /* Slightly reduced fixed width for labels */
            font-weight: normal;
            padding-right: 8px;
            white-space: nowrap; /* Prevent labels from wrapping */
        }

        .biodata-value-col {
            flex: 1 1 auto;
            word-wrap: break-word;
            overflow-wrap: break-word;
            text-align: left; /* Ensure value text is left aligned */
        }

        .biodata-extra-col {
            flex: 0 1 auto;
            text-align: right;
            padding-left: 10px;
            white-space: nowrap;
            min-width: 100px; /* Reduced min-width */
            margin-left: auto; /* Push extra col to the right */
        }

        .biodata-extra-col:empty {
            min-width: 0;
            padding-left: 0;
        }

        .address-row {
            align-items: flex-start;
        }

        .address-value {
            line-height: 1.3;
        }

        .header-extra {
            font-weight: bold;
        }

        /* Ensure the styles also work for printing */
        @media print {
            body {
                background-color: #fff; /* White background for printing */
            }
            /* Styles for the overall table layout */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px; /* Add some space below the table */
            }

            td, th {
                border: 1px solid black !important;
                padding: 0; /* Remove padding from td itself, apply it to inner container */
                vertical-align: top;
                background-color: white;
                text-align: left;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            td {
                width: 50%; /* Each cell takes half the width */
            }

            tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Styles for the detailed biodata content inside each cell */
            .biodata-container {
                font-family: Arial, sans-serif;
                color: black;
                line-height: 1.4;
                padding: 10px; /* Padding inside the container */
                margin: 5px; /* Optional: small margin inside the td border */
                page-break-inside: avoid;
                break-inside: avoid;
                display: flex;
                flex-direction: column;
            }

            .biodata-row {
                display: flex;
                margin-bottom: 4px; /* Smaller space between rows */
                align-items: baseline;
                font-size: 0.9em; /* Slightly smaller font size */
            }

            .biodata-label-col {
                flex: 0 0 90px; /* Slightly reduced fixed width for labels */
                font-weight: normal;
                padding-right: 8px;
                white-space: nowrap; /* Prevent labels from wrapping */
            }

            .biodata-value-col {
                flex: 1 1 auto;
                word-wrap: break-word;
                overflow-wrap: break-word;
                text-align: left; /* Ensure value text is left aligned */
            }

            .biodata-extra-col {
                flex: 0 1 auto;
                text-align: right;
                padding-left: 10px;
                white-space: nowrap;
                min-width: 100px; /* Reduced min-width */
                margin-left: auto; /* Push extra col to the right */
            }

            .biodata-extra-col:empty {
                min-width: 0;
                padding-left: 0;
            }

            .address-row {
                align-items: flex-start;
            }

            .address-value {
                line-height: 1.3;
            }

            .header-extra {
                font-weight: bold;
            }
            /* Hide print button in print view */
            #button-print {
                display: none;
            }
        }
    </style>
@endpush
