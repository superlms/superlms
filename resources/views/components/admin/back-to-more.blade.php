@php
    // Admin routes live under /{organization}; the segment must equal the
    // signed-in admin's organization_id. Fall back to it when the segment is
    // missing so the link always resolves.
    $backOrg = request()->route('organization') ?? auth()->user()?->organization_id;
@endphp
<a href="{{ route('admin.more', ['organization' => $backOrg]) }}" wire:navigate title="Back to More"
    class="p-2 -ml-2 text-gray-400 hover:text-indigo-600 transition-colors flex-shrink-0">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
</a>
