<?php

namespace App\Filament\Resources;

use App\Enums\JenisKelamin;
use App\Enums\KelompokKediri;
use App\Enums\StatusKelanjutanKediri;
use App\Filament\Exports\PesertaKediriExporter;
use App\Filament\Imports\UpdateRFIDPesertaKediriImporter;
use App\Filament\Imports\UpdateStatusTesPesertaKediriImporter;
use App\Filament\Resources\PesertaKediriResource\Pages\CreatePesertaKediri;
use App\Filament\Resources\PesertaKediriResource\Pages\EditPesertaKediri;
use App\Filament\Resources\PesertaKediriResource\Pages\ListPesertaKediris;
use App\Filament\Resources\PesertaKediriResource\Pages\ViewPesertaKediri;
use App\Models\Periode;
use App\Models\PesertaKediri;
use App\Models\Ponpes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PesertaKediriResource extends Resource
{
    protected static ?string $model = PesertaKediri::class;
    protected static ?string $slug = 'peserta-kediri';
    protected static ?string $modelLabel = 'Peserta Kediri';
    protected static ?string $pluralModelLabel = 'Peserta Kediri';
    protected static ?string $recordTitleAttribute = 'recordTitle';
    protected static ?string $navigationLabel = 'Peserta Kediri';
    protected static ?string $navigationGroup = 'Pengetesan Kediri';
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(PesertaKediri::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(PesertaKediri::getColumns())
            ->defaultSort('siswa.nama_lengkap')
            ->filters([
                SelectFilter::make('id_periode')
                    ->label('Periode Tes')
                    ->options(Periode::orderBy('id_periode', 'desc')->get()->pluck('id_periode', 'id_periode'))
                    ->searchable(),
                Filter::make('jenis_kelamin')
                    ->form([
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->default(JenisKelamin::LAKI_LAKI->value),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['jenis_kelamin'],
                                fn (Builder $query): Builder =>
                                $query->whereHas('siswa', function ($query) use ($data) {
                                    $query->where('jenis_kelamin', $data['jenis_kelamin']);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['jenis_kelamin']) {
                            return null;
                        }

                        $jenis_kelamin = $data['jenis_kelamin'] == JenisKelamin::LAKI_LAKI->value ? 'Laki-laki' : 'Perempuan';

                        return 'Jenis Kelamin: ' . $jenis_kelamin;
                    }),
                SelectFilter::make('kelompok')
                    ->label('Kelompok')
                    ->options(KelompokKediri::class),
                SelectFilter::make('id_ponpes')
                    ->label('Asal Ponpes')
                    ->options(
                        Ponpes::with('daerah')
                            ->join('tb_daerah', 'tb_ponpes.id_daerah', '=', 'tb_daerah.id_daerah')
                            ->orderByRaw('LOWER(tb_daerah.n_daerah), LOWER(tb_ponpes.n_ponpes)')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'id' => $item->id_ponpes,
                                    'name' => "{$item->n_ponpes} ({$item->daerah->n_daerah})"
                                ];
                            })
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->preload()
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Action::make('assign_cocard')
                    ->label('Assign Cocard')
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle')
                    ->modalHeading('Konfirmasi Assign Cocard')
                    ->modalDescription('Untuk melanjutkan proses assign cocard, mohon ketikkan teks berikut persis di kolom bawah.')
                    ->modalSubmitActionLabel('Assign Cocard Sekarang')
                    ->form([
                        TextInput::make('confirmation_phrase')
                            ->label('Ketik: "Saya yakin untuk meng-assign cocard peserta!"')
                            ->required()
                            ->rules(['in:Saya yakin untuk meng-assign cocard peserta!'])
                            ->validationMessages([
                                'in' => 'Teks konfirmasi yang Anda masukkan tidak sesuai. Mohon periksa kembali.',
                            ]),
                    ])
                    ->action(function (array $data) {
                        PesertaKediri::updateNomorCocardAndKelompok();
                        \Filament\Notifications\Notification::make()
                            ->title('Assign cocard peserta tes berhasil!')
                            ->success()
                            ->send();
                }),
                Tables\Actions\ImportAction::make('update_status_tes')
                    ->label('Update Status Tes')
                    ->importer(UpdateStatusTesPesertaKediriImporter::class)
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle'),
                Tables\Actions\ImportAction::make('update_rfid')
                    ->label('Update RFID')
                    ->importer(UpdateRFIDPesertaKediriImporter::class)
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-circle'),
                ExportAction::make('ekspor_peserta')
                    ->label('Ekspor')
                    ->exporter(PesertaKediriExporter::class),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('updateStatusKelanjutan')
                        ->label('Ubah Kelanjutan') // Set a user-friendly label
                        ->icon('heroicon-o-pencil-square') // Choose an appropriate icon
                        ->requiresConfirmation() // Make the user confirm the action
                        ->color('danger')
                        ->form([
                            Select::make('status_kelanjutan')
                                ->label('Status Kelanjutan')
                                ->options(StatusKelanjutanKediri::class)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->status_kelanjutan = $data['status_kelanjutan'];
                                $record->save();
                            }
                            Notification::make()
                                ->title('Status Kelanjutan Peserta Diperbarui')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->selectCurrentPageOnly()
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPesertaKediris::route('/'),
            'create' => CreatePesertaKediri::route('/create'),
            'view' => ViewPesertaKediri::route('/{record}'),
            'edit' => EditPesertaKediri::route('/{record}/edit'),
        ];
    }
}
