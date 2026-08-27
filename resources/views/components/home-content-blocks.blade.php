@php
    use App\Enums\ContentBlockType;
    use App\Models\ContentBlock;

    $blocks = ContentBlock::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();
@endphp

@foreach ($blocks as $block)
    @switch($block->type)
        @case (ContentBlockType::Hero)
            <x-content-blocks.hero :block="$block" />
            @break

        @case (ContentBlockType::Banner)
            <x-content-blocks.banner :block="$block" />
            @break

        @case (ContentBlockType::FeaturedCategories)
            <x-content-blocks.featured-categories :block="$block" />
            @break

        @case (ContentBlockType::FeaturedProducts)
            <x-content-blocks.featured-products :block="$block" />
            @break

        @case (ContentBlockType::FeaturedVendors)
            <x-content-blocks.featured-vendors :block="$block" />
            @break

        @case (ContentBlockType::PromoGrid)
            <x-content-blocks.promo-grid :block="$block" />
            @break
    @endswitch
@endforeach
