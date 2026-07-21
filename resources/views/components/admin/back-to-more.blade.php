@php
    // Admin routes live under /{organization}; the segment must equal the
    // signed-in admin's organization_id. Fall back to it when the segment is
    // missing so the link always resolves.
    $backOrg = request()->route('organization') ?? auth()->user()?->organization_id;
@endphp
<div class="bg-white border-b border-gray-100 px-4 sm:px-6 py-2">
    <a href="{{ route('admin.more', ['organization' => $backOrg]) }}" wire:navigate
        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back to More
    </a>
</div>
