<div class="flex items-center justify-between mt-4 text-center">
    <div class="flex-1">
        <div class="text-sm text-gray-700 font-semibold">{{\App\Enums\HasilSistem::BELUM_PENGETESAN->getLabel()}}</div>
        <div class="text-lg">{{ $data[\App\Enums\HasilSistem::BELUM_PENGETESAN->getLabel()] }}</div>
    </div>

    <div class="flex-1">
        <div class="text-sm text-success-700 font-semibold">{{\App\Enums\HasilSistem::LULUS->getLabel()}}</div>
        <div class="text-lg">{{ $data[\App\Enums\HasilSistem::LULUS->getLabel()] }}</div>
    </div>

    <div class="flex-1">
        <div class="text-sm text-danger-700 font-semibold">Tidak Lulus</div>
        <div class="text-lg">{{ $data[\App\Enums\HasilSistem::TIDAK_LULUS_AKADEMIK->getLabel()] }}</div>
    </div>

    <div class="flex-1">
        <div class="text-sm text-gray-700 font-semibold">{{\App\Enums\HasilSistem::PERLU_MUSYAWARAH->getLabel()}}</div>
        <div class="text-lg">{{ $data[\App\Enums\HasilSistem::PERLU_MUSYAWARAH->getLabel()] }}</div>
    </div>
</div>
