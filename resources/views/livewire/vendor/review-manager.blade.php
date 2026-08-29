<div class="px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Reviews</h1>

    @if ($reviews->isEmpty())
        <div class="mt-8">
            <x-ui.empty-state title="No reviews yet" message="Reviews on your products will show up here." />
        </div>
    @else
        <div class="mt-6 space-y-6">
            @foreach ($reviews as $review)
                <div wire:key="review-{{ $review->id }}" class="overflow-hidden rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $review->product->name }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <x-ui.rating-stars :rating="$review->rating" />
                                <span class="text-sm text-gray-500">{{ $review->user->name }}</span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $review->created_at?->format('M j, Y') }}</span>
                    </div>

                    @if ($review->body)
                        <p class="mt-3 text-sm text-gray-600">{{ $review->body }}</p>
                    @endif

                    <div class="mt-4 border-l-2 border-primary/30 pl-4">
                        @if ($replyingToId === $review->id)
                            <form wire:submit.prevent="saveReply({{ $review->id }})" class="space-y-2">
                                <label for="reply-{{ $review->id }}" class="block text-xs font-medium text-gray-500">Your reply</label>
                                <textarea
                                    id="reply-{{ $review->id }}"
                                    wire:model="replyBody"
                                    rows="3"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    placeholder="Write a reply to this review..."
                                ></textarea>
                                @error('replyBody')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="flex items-center gap-2">
                                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveReply({{ $review->id }})">
                                        Save reply
                                    </x-ui.button>
                                    <button type="button" wire:click="cancelReply" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        @elseif ($review->vendor_reply)
                            <p class="text-xs font-medium text-gray-500">Your reply &middot; {{ $review->vendor_replied_at?->format('M j, Y') }}</p>
                            <p class="mt-1 text-sm text-gray-700">{{ $review->vendor_reply }}</p>
                            <button
                                type="button"
                                wire:click="startReply({{ $review->id }})"
                                class="mt-2 text-sm font-medium text-primary hover:underline"
                            >
                                Edit reply
                            </button>
                        @else
                            <button
                                type="button"
                                wire:click="startReply({{ $review->id }})"
                                class="text-sm font-medium text-primary hover:underline"
                            >
                                Reply to this review
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $reviews->links() }}
        </div>
    @endif
</div>
