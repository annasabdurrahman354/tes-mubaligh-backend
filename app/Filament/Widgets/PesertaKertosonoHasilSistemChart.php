<?php

namespace App\Filament\Widgets;

use App\Enums\HasilSistem;
use App\Enums\StatusTesKertosono;
use App\Models\PesertaKediri;
use App\Models\PesertaKertosono;
use Illuminate\Contracts\View\View;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PesertaKertosonoHasilSistemChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'pesertaKertosonoHasilSistemChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Hasil Sistem Tes Kertosono';

    /**
     * Sort
     */
    protected static ?int $sort = 4;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 215;
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    /**
     * Widget Footer
     */
    protected function getFooter(): string | View
    {
        $activeFilter = $this->filter;

        $periode_tes_id = getPeriodeTes();
        $peserta = PesertaKertosono::where('periode_id', $periode_tes_id)
            ->withHasilSistem()
            ->with(['siswa'])
            ->get();

        if ($activeFilter !== 'all') {
            $peserta = $peserta->where('siswa.jenis_kelamin', $activeFilter);
        }

        $data = [
            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => $peserta->where('hasil_sistem', HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel())->count(),
            HasilSistem::LULUS->getLabel() => $peserta->where('hasil_sistem', HasilSistem::LULUS->getLabel())->count(),
            HasilSistem::PERLU_MUSYAWARAH->getLabel() => $peserta->where('hasil_sistem', HasilSistem::PERLU_MUSYAWARAH->getLabel())->count(),
            HasilSistem::BELUM_PENGETESAN->getLabel() => $peserta->where('hasil_sistem', HasilSistem::BELUM_PENGETESAN->getLabel())->count(),
        ];

        return view('filament.widgets.peserta-kertosono-chart.footer', ['data' => $data]);
    }

    protected static ?string $loadingIndicator = 'Memuat...';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Semua Peserta',
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
        ];
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $activeFilter = $this->filter;

        $periode_tes_id = getPeriodeTes();
        $peserta = PesertaKertosono::where('periode_id', $periode_tes_id)
            ->whereIn('status_tes', [
                StatusTesKertosono::AKTIF->value,
                StatusTesKertosono::LULUS->value,
                StatusTesKertosono::TIDAK_LULUS_AKHLAK->value,
                StatusTesKertosono::TIDAK_LULUS_AKADEMIK->value,
                StatusTesKertosono::PERLU_MUSYAWARAH->value
            ])
            ->withHasilSistem()
            ->with(['siswa'])
            ->get();

        if ($activeFilter !== 'all') {
            $peserta = $peserta->where('siswa.jenis_kelamin', $activeFilter);
        }

        $statuses = [
            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => $peserta->where('hasil_sistem', HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel())->count(),
            HasilSistem::LULUS->getLabel() => $peserta->where('hasil_sistem', HasilSistem::LULUS->getLabel())->count(),
            HasilSistem::PERLU_MUSYAWARAH->getLabel() => $peserta->where('hasil_sistem', HasilSistem::PERLU_MUSYAWARAH->getLabel())->count(),
            HasilSistem::BELUM_PENGETESAN->getLabel() => $peserta->where('hasil_sistem', HasilSistem::BELUM_PENGETESAN->getLabel())->count(),
        ];

        return [
            'chart' => [
                'type' => 'radialBar',
                'height' => 280,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                number_format(count($peserta) > 0 ? ($statuses[HasilSistem::LULUS->getLabel()] / count($peserta)) * 100 : 0, 3)
            ],
            'plotOptions' => [
                'radialBar' => [
                    'startAngle' => -140,
                    'endAngle' => 130,
                    'hollow' => [
                        'size' => '60%',
                        'background' => 'transparent',
                    ],
                    'track' => [
                        'background' => 'transparent',
                        'strokeWidth' => '100%',
                    ],
                    'dataLabels' => [
                        'show' => true,
                        'name' => [
                            'show' => true,
                            'offsetY' => -10,
                            'fontWeight' => 600,
                            'fontFamily' => 'inherit',
                        ],
                        'value' => [
                            'show' => true,
                            'fontWeight' => 600,
                            'fontSize' => '24px',
                            'fontFamily' => 'inherit',
                        ],
                    ],

                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'horizontal',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#f59e0b'],
                    'inverseColors' => true,
                    'opacityFrom' => 1,
                    'opacityTo' => 0.6,
                    'stops' => [30, 70, 100],
                ],
            ],
            'stroke' => [
                'dashArray' => 10,
            ],
            'labels' => [HasilSistem::LULUS->getLabel()],
            'colors' => ['#16a34a'],
        ];
    }
}
