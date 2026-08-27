<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * Curated whitelist of fonts that are actually available in the
     * storefront theme. Kept out of a free-text input so an admin can't
     * pick a font that was never loaded.
     */
    public const FONT_FAMILY_OPTIONS = [
        'Instrument Sans' => 'Instrument Sans',
        'Inter' => 'Inter',
        'Poppins' => 'Poppins',
        'Roboto' => 'Roboto',
        'Nunito Sans' => 'Nunito Sans',
        'Work Sans' => 'Work Sans',
        'DM Sans' => 'DM Sans',
        'Plus Jakarta Sans' => 'Plus Jakarta Sans',
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    protected string $view = 'filament.pages.site-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Branding')
                    ->schema([
                        TextInput::make('site_name')
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('site'),
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('site'),
                        ColorPicker::make('primary_color')
                            ->required(),
                        ColorPicker::make('secondary_color')
                            ->required(),
                        ColorPicker::make('accent_color')
                            ->required(),
                        Select::make('font_family')
                            ->label('Font family')
                            ->options(self::FONT_FAMILY_OPTIONS)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Marketplace')
                    ->schema([
                        TextInput::make('default_commission_percent')
                            ->label('Default commission')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        TextInput::make('contact_email')
                            ->email(),
                        TextInput::make('contact_phone')
                            ->tel(),
                        KeyValue::make('social_links')
                            ->keyLabel('Platform')
                            ->valueLabel('URL')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::current()->update($data);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
