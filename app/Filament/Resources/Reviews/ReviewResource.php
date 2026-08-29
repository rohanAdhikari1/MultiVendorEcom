<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Resources\Reviews\Schemas\ReviewInfolist;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $recordTitleAttribute = 'body';

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
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
            'index' => ListReviews::route('/'),
            'view' => ViewReview::route('/{record}'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    /**
     * Reviews originate from customers on the storefront, not the admin
     * panel — admins moderate (toggle visibility) but never author reviews.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * `is_visible` already covers the moderation need without destroying
     * data, so deletion is disabled here (no DeleteAction wired into any
     * page/table either — this is a defense-in-depth belt-and-braces
     * override, since ReviewPolicy::before() would otherwise let an admin
     * delete via the policy's blanket bypass).
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
