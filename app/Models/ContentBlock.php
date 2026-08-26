<?php

namespace App\Models;

use App\Enums\ContentBlockType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ContentBlockType $type
 * @property string|null $title
 * @property string|null $subtitle
 * @property array<string, mixed> $payload
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'title', 'subtitle', 'payload', 'sort_order', 'is_active'])]
class ContentBlock extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ContentBlockType::class,
            'payload' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
