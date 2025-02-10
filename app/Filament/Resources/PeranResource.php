<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeranResource\Pages\ManagePeran;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PeranResource extends Resource
{
    protected static ?string $slug = 'peran';
    protected static ?string $modelLabel = 'Peran';
    protected static ?string $pluralModelLabel = 'Peran';
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Peran';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?int $navigationSort = 43;

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('name')
                    ->label('Nama Peran')
                    ->content(fn ($record) => $record->name),
                Select::make('users')
                    ->label('Pemilik Peran')
                    ->multiple()
                    ->native(true)
                    ->preload()
                    ->options(
                        User::get()
                            ->pluck('recordTitle', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Jumlah Pemilik')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('users')
                    ->label('Pemilik')
                    ->getStateUsing(function ($record): string{
                        $userIds = DB::table(config('permission.table_names.model_has_roles'))
                            ->where('role_id', $record->id)
                            ->where('model_type', User::class)
                            ->get()
                            ->pluck('model_id')
                            ->toArray();
                        $users = User::whereIn('id', $userIds)
                            ->get()
                            ->pluck('nama')
                            ->toArray();
                        return implode(";", $users);
                    })
                    ->separator(';')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        $roleId = $record->id;

                        $userIds = DB::table(config('permission.table_names.model_has_roles'))
                            ->where('role_id', $roleId)
                            ->where('model_type', User::class)
                            ->get()
                            ->pluck('model_id')
                            ->toArray();
                        $users = User::whereIn('id', $userIds)
                            ->get()
                            ->pluck('recordTitle')
                            ->toArray();

                        $data['users'] = $users;

                        return $data;
                    }),

                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, $record): array {
                        $roleId = $record->id;

                        $userIds = DB::table(config('permission.table_names.model_has_roles'))
                            ->where('role_id', $roleId)
                            ->where('model_type', User::class)
                            ->get()
                            ->pluck('model_id')
                            ->toArray();
                        $users = User::whereIn('id', $userIds)
                            ->get()
                            ->pluck('id')
                            ->toArray();

                        $data['users'] = $users;

                        return $data;
                    })
                    ->action(function (array $data, $record): void {
                        $roleId = $record->id;

                        $userIDs = $data['users'];
                        $newRoles = [];
                        foreach ($userIDs as $id){
                            $newRoles[] = [
                                'model_id' => $id,
                                'role_id' => $roleId,
                                'model_type' => User::class
                            ];
                        }

                        DB::table(config('permission.table_names.model_has_roles'))
                            ->where('role_id', $roleId)
                            ->where('model_type', User::class)
                            ->delete();

                        DB::table(config('permission.table_names.model_has_roles'))
                            ->insert($newRoles);
                    })

            ])
            ->bulkActions([

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
            'index' => ManagePeran::route('/'),
        ];
    }
}
