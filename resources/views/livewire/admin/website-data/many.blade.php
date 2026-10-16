{{--
    Several photos picked at once, uploaded one after another to $target —
    Livewire's S3 temporary uploads take one file at a time — each placed by
    the component as it arrives. Params: $target (uploads.<block>), $label,
    $class (the label's), $icon (svg markup).
--}}
<label class="{{ $class }}" x-data="{ left: 0 }">
    {!! $icon !!}
    <span x-show="! left">{{ $label }}</span>
    <span x-show="left" x-cloak x-text="'Uploading… ' + left + ' left'"></span>
    <input type="file" accept="image/*" multiple class="sr-only" x-bind:disabled="left > 0"
        x-on:change="const files = [...$event.target.files]; $event.target.value = '';
            left = files.length;
            for (const file of files) {
                await new Promise((done) => $wire.upload('{{ $target }}', file, done, done));
                left--;
            }">
</label>
