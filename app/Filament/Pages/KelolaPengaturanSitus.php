<?php

namespace App\Filament\Pages;

use App\Settings\PengaturanSitus;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use function Filament\Support\is_app_url;

class KelolaPengaturanSitus extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = PengaturanSitus::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $settings = app(static::getSettings());

        $data = $this->mutateFormDataBeforeFill($settings->toArray());

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site')
                    ->label('Situs')
                    ->description('Kelola pengaturan dasar.')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('brand_name')
                                ->label('Nama Brand')
                                ->required(),
                            Forms\Components\Select::make('site_active')
                                ->label('Status Situs')
                                ->options([
                                    0 => "Not Active",
                                    1 => "Active",
                                ])
                                ->native(false)
                                ->required(),
                        ]),
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('brand_logoHeight')
                                ->label('Tinggi Logo Brand')
                                ->required()
                                ->columnSpanFull()
                                ->maxWidth('w-1/2'),
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\FileUpload::make('brand_logo')
                                    ->label('Logo Brand')
                                    ->image()
                                    ->directory('sites')
                                    ->visibility('public')
                                    ->moveFiles()
                                    ->required(),

                                Forms\Components\FileUpload::make('site_favicon')
                                    ->label('Favicon Situs')
                                    ->image()
                                    ->directory('sites')
                                    ->visibility('public')
                                    ->moveFiles()
                                    ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon'])
                                    ->required(),
                            ]),
                        ])->columns(4),
                    ]),
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Color Palette')
                            ->schema([
                                Forms\Components\ColorPicker::make('site_theme.primary')
                                    ->label('Utama'),
                                Forms\Components\ColorPicker::make('site_theme.secondary')
                                    ->label('Sekunder'),
                                Forms\Components\ColorPicker::make('site_theme.gray')
                                    ->label('Abu-abu'),
                                Forms\Components\ColorPicker::make('site_theme.success')
                                    ->label('Sukses'),
                                Forms\Components\ColorPicker::make('site_theme.danger')
                                    ->label('Bahaya'),
                                Forms\Components\ColorPicker::make('site_theme.info')
                                    ->label('Informasi'),
                                Forms\Components\ColorPicker::make('site_theme.warning')
                                    ->label('Peringatan'),
                            ])
                            ->columns(3),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->mutateFormDataBeforeSave($this->form->getState());

            $settings = app(static::getSettings());

            $settings->fill($data);
            $settings->save();

            Notification::make()
                ->title('Pengaturan situs diperbarui.')
                ->success()
                ->send();

            $this->redirect(static::getUrl(), navigate: FilamentView::hasSpaMode() && is_app_url(static::getUrl()));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pengaturan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Situs';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pengaturan Situs';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Pengaturan Situs';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Kelola pengaturan situs di sini.';
    }
}
