<?php

use App\Enums\BorderRadiusScale;
use App\Enums\ButtonStyle;
use App\Enums\LayoutDensity;
use App\Enums\ShadowStyle;
use App\Filament\Pages\SiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SiteSettingSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(SiteSettingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // `is_active` is excluded from User::$fillable, so the in-memory model
    // needs a refresh before being handed to actingAs()/Livewire::actingAs()
    // — see AdminResourceAccessTest for the full explanation.
    $this->admin->refresh();
});

it('saves the layout and style settings from the admin panel', function (): void {
    Livewire::actingAs($this->admin)
        ->test(SiteSettings::class)
        ->fillForm([
            'border_radius_scale' => BorderRadiusScale::Pill->value,
            'shadow_style' => ShadowStyle::Elevated->value,
            'layout_density' => LayoutDensity::Spacious->value,
            'button_style' => ButtonStyle::Outline->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = SiteSetting::current()->fresh();

    expect($settings)->not->toBeNull()
        ->and($settings->border_radius_scale)->toBe(BorderRadiusScale::Pill)
        ->and($settings->shadow_style)->toBe(ShadowStyle::Elevated)
        ->and($settings->layout_density)->toBe(LayoutDensity::Spacious)
        ->and($settings->button_style)->toBe(ButtonStyle::Outline);
});

it('exposes form fields for every new layout and style setting', function (): void {
    $component = Livewire::actingAs($this->admin)->test(SiteSettings::class);

    foreach (['border_radius_scale', 'shadow_style', 'layout_density', 'button_style'] as $field) {
        $component->assertFormFieldExists($field);
    }
});
