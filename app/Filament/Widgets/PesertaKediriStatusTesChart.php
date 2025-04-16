<?php

namespace App\Filament\Widgets;

use App\Models\Periode;
use App\Models\Ponpes;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class PesertaKediriStatusTesChart extends ApexChartWidget
{
    use HasWidgetShield;
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'pesertaKediriStatusTesChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Peserta Tes Kediri';

    /**
     * Sort
     */
    protected static ?int $sort = 1;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 300;
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;
    protected static ?string $loadingIndicator = 'Memuat...';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $id_periode = getPeriodeTes();
        return array_merge(
            [null => 'Semua Ponpes'],
            Ponpes::whereHas('pesertaKediri', function ($query) use ($id_periode) {
                    $query->where('id_periode', $id_periode);
                })
                ->orderBy('n_ponpes')
                ->get()
                ->pluck('n_ponpes', 'id_ponpes')
                ->toArray()
        );
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $activeFilter = $this->filter;
        $id_periode = getPeriodeTes();

        $periode = Periode::where('id_periode', $id_periode)
            ->withStatusTesPesertaKediri($activeFilter)
            ->first();

        $maleData = [
            $periode->count_pra_tes_male ?? 0,
            $periode->count_rejected_barang_male ?? 0,
            $periode->count_rejected_materi_male ?? 0,
            $periode->count_tunda_male ?? 0,
            $periode->count_aktif_male ?? 0,
            $periode->count_lulus_male ?? 0,
            $periode->count_tidak_lulus_male ?? 0,
        ];

        $femaleData = [
            $periode->count_pra_tes_female ?? 0,
            $periode->count_rejected_barang_female ?? 0,
            $periode->count_rejected_materi_female ?? 0,
            $periode->count_tunda_female ?? 0,
            $periode->count_aktif_female ?? 0,
            $periode->count_lulus_female ?? 0,
            $periode->count_tidak_lulus_female ?? 0,
        ];

        return [
            'series' => [
                [
                    'name' => 'Laki-laki',
                    'data' => $maleData
                ],
                [
                    'name' => 'Perempuan',
                    'data' => $femaleData
                ]
            ],
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'stacked' => true,
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '55%',
                    'borderRadius' => 10,
                    'borderRadiusApplication' => 'end', // 'around', 'end'
                    'borderRadiusWhenStacked' => 'last', // 'all', 'last'
                ]
            ],
            'dataLabels' => [
                'enabled' => false
            ],
            'stroke' => [
                'show' => true,
                'width' => 2,
                'colors' => ['transparent']
            ],
            'xaxis' => [
                'categories' => [
                    'pra-tes',
                    'rejected-barang',
                    'rejected-materi',
                    'tunda',
                    'aktif',
                    'lulus',
                    'tidak-lulus'
                ]
            ],
            'fill' => [
                'opacity' => 1
            ],
            'tooltip' => [
                'y' => [
                    'formatter' => 'function (val) { return val; }'
                ]
            ]
        ];
    }
}
