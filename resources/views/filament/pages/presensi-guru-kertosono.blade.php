<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="generatePresensi" class="fi-form">
            {{ $this->form }}
        </form>

        @if($attendanceData != [] && $distinctTanggalSesi != [])
            <x-filament::section wire:loading.remove>
                <x-slot name="heading">
                    Presensi Guru Kertosono
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::button wire:click="printPresensi" id="button-print" color="primary" wire:loading.remove>
                        Ekspor Excel
                    </x-filament::button>
                    <x-filament::button color="primary" disabled wire:loading>
                        Loading...
                    </x-filament::button>
                </x-slot>

                <div id="view-print" class="overflow-auto mt-4">
                    <table id="table-print" style="table-layout: auto; border-collapse: collapse; border: 1px solid #CBD5E1; font-size: 0.875rem; line-height: 1.25rem;" cellpadding="5">
                        <thead>
                        <tr>
                            <th style="border: 1px solid #CBD5E1; text-align: left;" colspan="{{ array_sum(array_map('count', $distinctTanggalSesi)) + 5 }}">
                                PRESENSI PENYIMAKAN TES KERTOSONO
                            </th>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #CBD5E1; text-align: left;" colspan="{{ array_sum(array_map('count', $distinctTanggalSesi)) + 5 }}">
                                Tanggal {{ ucfirst($periodeLabel) }}
                            </th>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #CBD5E1; text-align: center;" rowspan="2">No</th>
                            <th style="border: 1px solid #CBD5E1; text-align: center;" rowspan="2">Nama</th>
                            @foreach($distinctTanggalSesi as $date => $sessions)
                                <th style="border: 1px solid #CBD5E1; text-align: center;" colspan="{{ count($sessions) }}">
                                    {{ \Carbon\Carbon::parse($date)->format('d') }}
                                </th>
                            @endforeach
                            <th style="border: 1px solid #CBD5E1; text-align: center;" rowspan="2">Jumlah<br>Hadir</th>
                            <th style="border: 1px solid #CBD5E1; text-align: center;" rowspan="2">Total<br>Pertemuan</th>
                            <th style="border: 1px solid #CBD5E1; text-align: center;" rowspan="2">Total<br>Simakan</th>
                        </tr>
                        <tr>
                            @foreach($distinctTanggalSesi as $date => $sessions)
                                @foreach($sessions as $session)
                                    <th style="border: 1px solid #CBD5E1; text-align: center;">
                                        @switch($session['label'])
                                            @case('fajar') F @break
                                            @case('pagi') P @break
                                            @case('siang') S @break
                                            @case('malam') M @break
                                            @default ?
                                        @endswitch
                                    </th>
                                @endforeach
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($attendanceData as $guru)
                            <tr>
                                <td style="border: 1px solid #CBD5E1; text-align: left;">{{ $guru['no'] }}</td>
                                <td style="border: 1px solid #CBD5E1; text-align: left;">{{ $guru['nama'] }}</td>
                                @foreach($distinctTanggalSesi as $date => $sessions)
                                    @foreach($sessions as $session)
                                        @php
                                            $val = $guru['tanggal'][$date]['sesi'][$session['label']] ?? null;
                                        @endphp
                                        @if($val)
                                            <td style="border: 1px solid #CBD5E1; text-align: center; background-color: greenyellow;">
                                                {{ $val }}
                                            </td>
                                        @elseif($val === 0)
                                            <td style="border: 1px solid #CBD5E1; text-align: center;"></td>
                                        @else
                                            <td style="border: 1px solid #CBD5E1; text-align: center;">&nbsp;</td>
                                        @endif
                                    @endforeach
                                @endforeach
                                <td style="border: 1px solid #CBD5E1; text-align: right;">{{ $guru['jumlah']['hadir'] }}</td>
                                <td style="border: 1px solid #CBD5E1; text-align: right;">{{ $guru['total_pertemuan'] }}</td>
                                <td style="border: 1px solid #CBD5E1; text-align: right;">{{ $guru['total_simak'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
