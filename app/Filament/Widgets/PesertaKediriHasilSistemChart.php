<?php

namespace App\Filament\Widgets;

use App\Enums\HasilSistem;
use App\Enums\StatusTesKediri;
use App\Models\PesertaKediri;
use Illuminate\Contracts\View\View;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PesertaKediriHasilSistemChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'pesertaKediriHasilSistemChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Hasil Sistem Tes Kediri';

    /**
     * Sort
     */
    protected static ?int $sort = 3;

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
        $peserta = PesertaKediri::where('periode_id', $periode_tes_id)
            ->whereIn('status_tes', [
                StatusTesKediri::AKTIF->value,
                StatusTesKediri::LULUS->value,
                StatusTesKediri::TIDAK_LULUS_AKHLAK->value,
                StatusTesKediri::TIDAK_LULUS_AKADEMIK->value
            ])
            ->withHasilSistem()
            ->with(['siswa'])
            ->get();

        if ($activeFilter !== 'all') {
            $peserta = $peserta->where('siswa.jenis_kelamin', $activeFilter);
        }

        $statuses = [
            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => 0,
            HasilSistem::LULUS->getLabel() => 0,
            HasilSistem::BELUM_PENGETESAN->getLabel() => 0,
        ];

        foreach ($peserta as $p) {
            if (isset($statuses[$p->hasil_sistem])) {
                $statuses[$p->hasil_sistem]++;
            }
        }

        $data = [
            HasilSistem::LULUS->getLabel() => $statuses[HasilSistem::LULUS->getLabel()],
            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => $statuses[HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel()],
            HasilSistem::BELUM_PENGETESAN->getLabel() => $statuses[HasilSistem::BELUM_PENGETESAN->getLabel()],
        ];

        return view('filament.widgets.peserta-kediri-chart.footer', ['data' => $data]);
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
        $peserta = PesertaKediri::where('periode_id', $periode_tes_id)
            ->withHasilSistem()
            ->with(['siswa'])
            ->get();

        if ($activeFilter !== 'all') {
            $peserta = $peserta->where('siswa.jenis_kelamin', $activeFilter);
        }

        $statuses = [
            HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel() => $peserta->where('hasil_sistem', HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel())->count(),
            HasilSistem::LULUS->getLabel() => $peserta->where('hasil_sistem', HasilSistem::LULUS->getLabel())->count(),
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
