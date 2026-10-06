@php($modalId = 'pickup-photo-gallery-'.$reportId)

<div
    class="pickup-files"
    x-data="{
        photos: @js($photos),
        current: 0,
        viewerOpen: false,
        openPhoto(index) {
            this.current = index
            this.viewerOpen = true
            this.$dispatch('open-modal', { id: @js($modalId) })
        },
        previous() { this.current = (this.current - 1 + this.photos.length) % this.photos.length },
        next() { this.current = (this.current + 1) % this.photos.length },
    }"
    x-on:keydown.left.window="if (viewerOpen && photos.length > 1) previous()"
    x-on:keydown.right.window="if (viewerOpen && photos.length > 1) next()"
    x-on:close-modal.window="if ($event.detail.id === @js($modalId)) viewerOpen = false"
    x-on:close-modal-quietly.window="if ($event.detail.id === @js($modalId)) viewerOpen = false"
>
    @foreach ($files as $file)
        <div class="pickup-files__item">
            @if ($file['kind'] === 'site_photo')
                <button
                    type="button"
                    class="pickup-files__thumbnail-button"
                    aria-label="Открыть фото: {{ $file['name'] }}"
                    x-on:click="openPhoto({{ $file['photoIndex'] }})"
                >
                    <img
                        class="pickup-files__thumbnail"
                        src="{{ $file['url'] }}"
                        alt="{{ $file['name'] }}"
                        loading="lazy"
                    >
                    <span class="pickup-files__filename">{{ $file['name'] }}</span>
                </button>
            @else
                <a class="pickup-files__link" href="{{ $file['url'] }}">{{ $file['name'] }}</a>
            @endif
        </div>
    @endforeach

    @if ($photos !== [])
        <x-filament::modal :id="$modalId" width="7xl" heading="Фото площадки">
            <div class="pickup-photo-viewer">
                <img
                    class="pickup-photo-viewer__image"
                    x-bind:src="photos[current]?.url"
                    x-bind:alt="photos[current]?.name"
                >

                <template x-if="photos.length > 1">
                    <div>
                        <x-filament::icon-button
                            class="pickup-photo-viewer__previous"
                            color="gray"
                            icon="heroicon-o-chevron-left"
                            label="Предыдущее фото"
                            x-on:click="previous()"
                        />
                        <x-filament::icon-button
                            class="pickup-photo-viewer__next"
                            color="gray"
                            icon="heroicon-o-chevron-right"
                            label="Следующее фото"
                            x-on:click="next()"
                        />
                    </div>
                </template>
            </div>
            <div class="pickup-photo-viewer__caption">
                <span x-text="photos[current]?.name"></span>
                <span x-show="photos.length > 1" x-text="`${current + 1} / ${photos.length}`"></span>
            </div>
        </x-filament::modal>
    @endif
</div>

@once
    <style>
        .pickup-files { display: grid; grid-template-columns: repeat(auto-fill, 10rem); gap: 1rem; }
        .pickup-files__item { min-width: 0; }
        .pickup-files__link { color: rgb(37 99 235); cursor: pointer; text-decoration: underline; text-underline-offset: 2px; }
        .pickup-files__thumbnail-button { display: grid; gap: .35rem; justify-items: start; cursor: pointer; text-align: left; }
        .pickup-files__thumbnail { width: 10rem; height: 6rem; border-radius: .5rem; object-fit: cover; }
        .pickup-files__filename { max-width: 10rem; overflow: hidden; color: rgb(37 99 235); font-size: .875rem; text-overflow: ellipsis; white-space: nowrap; }
        .pickup-photo-viewer { position: relative; display: flex; min-height: 60vh; align-items: center; justify-content: center; overflow: hidden; border-radius: .5rem; background: #111827; }
        .pickup-photo-viewer__image { display: block; max-height: 72vh; max-width: 100%; object-fit: contain; }
        .pickup-photo-viewer__previous, .pickup-photo-viewer__next { position: absolute; top: 50%; z-index: 1; transform: translateY(-50%); background: rgb(255 255 255 / .9); }
        .pickup-photo-viewer__previous { left: 1rem; }
        .pickup-photo-viewer__next { right: 1rem; }
        .pickup-photo-viewer__caption { display: flex; justify-content: space-between; gap: 1rem; padding-top: .75rem; }
    </style>
@endonce
