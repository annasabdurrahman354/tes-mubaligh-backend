<x-filament-panels::page>
    <div>
        {{-- Form Filter --}}
        <form wire:submit="generateRekapan" class="fi-form">
            {{ $this->form }}
        </form>

        @if(!empty($rekapanPerKelompok) && $guruKediri?->isNotEmpty())
            <div id="view-print" class="mt-6 flex flex-col gap-8">
                {{-- Loop through each Kelompok in the results --}}
                @foreach($rekapanPerKelompok as $kelompok => $rekapan)
                    {{-- Wrapper for each kelompok's table, add class for page break --}}
                    <div class="kelompok-print-section mb-8">
                        <x-filament::section class="overflow-x-auto" wire:loading.remove wire:target="generateRekapan, generateAllKelompok">
                            <x-slot name="heading">
                                Rekap Penyimakan Peserta Kediri - Kelompok {{ $kelompok }}
                            </x-slot>
                            {{-- Only show print button on the first section or adjust logic if needed --}}
                            @if($loop->first)
                                <x-slot name="headerEnd">
                                    <x-filament::button wire:click="printRekapan" id="button-print" color="primary" icon="heroicon-o-printer">
                                        Print Hasil
                                    </x-filament::button>
                                </x-slot>
                            @endif

                            <table class="min-w-full divide-y divide-gray-300 border border-gray-300">
                                <thead class="bg-gray-100">
                                {{-- Calculate colspan based on current $rekapan count --}}
                                @php $colspan = $rekapan->count() + 1; @endphp
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 border border-gray-300 sticky left-0 bg-gray-100 z-10" colspan="{{ $colspan }}">
                                        Rekap Penyimakan Peserta Kediri
                                    </th>
                                </tr>
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 border border-gray-300 sticky left-0 bg-gray-100 z-10" colspan="{{ $colspan }}">
                                        Periode: {{ isset($data['id_periode']) ? (getYearAndMonthName($data['id_periode'])['monthName'] ?? '' ).' '.(getYearAndMonthName($data['id_periode'])['year'] ?? '') : 'N/A' }}
                                    </th>
                                </tr>
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 border border-gray-300 sticky left-0 bg-gray-100 z-10" colspan="{{ $colspan }}">
                                        Jenis Kelamin: {{ isset($data['jenis_kelamin']) ? ($data['jenis_kelamin'] == "L" ? 'Laki-laki' : ($data['jenis_kelamin'] == "P" ? 'Perempuan' : 'N/A')) : 'N/A' }}
                                    </th>
                                </tr>
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 border border-gray-300 sticky left-0 bg-gray-100 z-10" colspan="{{ $colspan }}">
                                        Kelompok: {{ $kelompok ?? 'N/A' }}
                                    </th>
                                </tr>
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-sm font-semibold text-gray-900 border border-gray-300 sticky left-0 bg-gray-100 z-10 align-bottom">
                                        Guru / Peserta
                                    </th>
                                    {{-- Header Kolom Peserta for the current kelompok --}}
                                    @foreach($rekapan as $peserta)
                                        <th scope="col" class="px-1 py-2 text-center text-sm font-semibold text-gray-900 border border-gray-300 whitespace-nowrap" style="writing-mode: vertical-rl; text-orientation: mixed; max-width: 20px;">
                                            {{-- Display Kelompok + Nomor Cocard --}}
                                            <span style="display: inline-block; transform: rotate(180deg);">
                                                        {{ $peserta->kelompok ?? '' }}{{ $peserta->nomor_cocard ?? '?' }}
                                                     </span>
                                            {{-- Optional: Add Name below --}}
                                            {{-- <br><span class="font-normal text-xs">{{ $peserta->siswa?->nama_lengkap }}</span> --}}
                                        </th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                {{-- Baris untuk setiap Guru --}}
                                @foreach($guruKediri as $guru)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm border sticky left-0 bg-white z-10">
                                            {{ $guru->nama }}
                                        </td>
                                        {{-- Kolom untuk setiap Peserta pada baris Guru ini for the current kelompok --}}
                                        @foreach($rekapan as $peserta)
                                            <td class="whitespace-nowrap px-2 py-2 text-sm text-center border">
                                                @if ($peserta->akademik->contains('guru_id', $guru->id))
                                                    <span class="text-lime-400 font-bold">✓</span> {{-- Tampilkan ceklis --}}
                                                @else
                                                    &nbsp; {{-- Kosongkan jika tidak ada --}}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </x-filament::section>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>

@push('styles')
    <style>
        @media print {
            .kelompok-print-section {
                page-break-before: always; /* Add page break before each section */
                margin-bottom: 0 !important; /* Remove margin in print */
                border: none !important; /* Remove section border in print */
                box-shadow: none !important; /* Remove section shadow in print */
            }
            .kelompok-print-section:first-child {
                page-break-before: avoid; /* Avoid page break before the very first section */
            }

            /* Ensure Filament section structure doesn't interfere */
            .fi-section {
                border: none !important;
                box-shadow: none !important;
                background: none !important;
            }
            .fi-section-header {
                display: none; /* Hide section headers in print unless needed */
            }

            table {
                border-collapse: collapse !important;
                width: 100% !important;
            }

            th, td {
                border: 1px solid #999 !important; /* Slightly darker border for print */
                padding: 3px 4px !important; /* Smaller padding */
                font-size: 8pt !important; /* Smaller font size */
                word-wrap: break-word; /* Allow text wrapping */
            }

            th {
                background-color: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-weight: bold; /* Ensure headers are bold */
                text-align: left; /* Align header text left by default */
            }

            th[scope="col"].text-center, td.text-center {
                text-align: center !important; /* Center specific cells */
            }

            th[scope="col"].whitespace-nowrap, td.whitespace-nowrap {
                white-space: normal !important; /* Allow wrapping in print even if nowrap was set */
            }
            /* Handle sticky header column in print */
            th.sticky, td.sticky {
                position: static !important; /* Remove sticky positioning */
                background-color: inherit !important; /* Use default background */
                /* Ensure first column header is still identifiable */
                background-color: #eee !important; /* Keep bg for sticky TH */
                z-index: auto !important;
            }

            td.sticky {
                background-color: #fff !important; /* Keep bg white for sticky TD */
            }

            /* Hide buttons and other non-print elements */
            #button-print, .fi-page > div > form, .fi-section-header-end {
                display: none !important;
            }

            /* Vertical headers */
            th[style*="writing-mode: vertical-rl"] {
                padding: 4px 2px !important; /* Adjust padding for vertical */
            }
            th[style*="writing-mode: vertical-rl"] span {
                display: inline-block;
                /*transform: rotate(180deg); Removed as it might cause issues, test*/
                white-space: nowrap;
            }
        }
    </style>
@endpush
