<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use App\Enums\ContentBlockType;

/**
 * Maps between the type-aware "virtual" form fields defined in
 * {@see ContentBlockForm} (e.g. `hero_image`, `featured_product_ids`,
 * `promo_items`) and the single `payload` JSON column that actually gets
 * persisted on the model. Only one set of virtual fields is relevant at a
 * time, depending on the selected `type`.
 */
class ContentBlockPayloadMapper
{
    /**
     * Virtual form field keys, keyed by the ContentBlockType value they
     * belong to, in the order they should be packed into `payload`.
     *
     * @return array<string, array<string, string>>
     */
    protected static function fieldMap(): array
    {
        return [
            ContentBlockType::Hero->value => [
                'hero_image' => 'image',
                'hero_cta_text' => 'cta_text',
                'hero_cta_url' => 'cta_url',
            ],
            ContentBlockType::Banner->value => [
                'banner_image' => 'image',
                'banner_link_url' => 'link_url',
            ],
            ContentBlockType::FeaturedProducts->value => [
                'featured_product_ids' => 'ids',
            ],
            ContentBlockType::FeaturedCategories->value => [
                'featured_category_ids' => 'ids',
            ],
            ContentBlockType::FeaturedVendors->value => [
                'featured_vendor_ids' => 'ids',
            ],
            ContentBlockType::PromoGrid->value => [
                'promo_items' => 'items',
            ],
        ];
    }

    /**
     * Unpack `payload` (loaded from the database) into the type-appropriate
     * virtual form fields, for the Edit form to fill itself with.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function unpack(array $data): array
    {
        $type = self::normalizeType($data['type'] ?? null);
        $payload = $data['payload'] ?? [];

        if (! is_array($payload)) {
            $payload = [];
        }

        foreach (self::fieldMap()[$type] ?? [] as $formField => $payloadKey) {
            $data[$formField] = $payload[$payloadKey] ?? null;
        }

        return $data;
    }

    /**
     * Pack the type-appropriate virtual form fields back into a single
     * `payload` array, and strip every virtual field out of $data so they
     * are never mass-assigned onto the model directly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function pack(array $data): array
    {
        $type = self::normalizeType($data['type'] ?? null);
        $fieldMap = self::fieldMap();

        $payload = [];

        foreach ($fieldMap[$type] ?? [] as $formField => $payloadKey) {
            $payload[$payloadKey] = $data[$formField] ?? null;
        }

        $data['payload'] = $payload;

        foreach ($fieldMap as $typeFields) {
            foreach (array_keys($typeFields) as $formField) {
                unset($data[$formField]);
            }
        }

        return $data;
    }

    protected static function normalizeType(mixed $type): ?string
    {
        return $type instanceof ContentBlockType ? $type->value : $type;
    }
}
