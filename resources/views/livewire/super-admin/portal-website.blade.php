<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900">School Websites</h1>
            </div>
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search schools..."
                    class="w-full sm:w-72 border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">School</th>
                        <th class="text-left font-semibold px-5 py-3 hidden sm:table-cell">Domain</th>
                        <th class="text-left font-semibold px-5 py-3">Status</th>
                        <th class="text-right font-semibold px-5 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($organizations as $org)
                        @php $site = $sites[$org->id] ?? null; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900">{{ $org->name }}</div>
                                <div class="text-xs text-gray-400">{{ $org->email }}</div>
                            </td>
                            <td class="px-5 py-3 hidden sm:table-cell text-gray-600">
                                {{ $site?->domain ?: '—' }}
                            </td>
                            <td class="px-5 py-3">
                                @if ($site && $site->status)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-green-50 text-green-700 border border-green-100">● Published</span>
                                @elseif ($site)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-100">● Draft</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">Not created</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('super-admin.school-website.edit', $org->id) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg">
                                    {{ $site ? 'Edit' : 'Create' }} Website
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">No schools found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $organizations->links() }}</div>
    </div>
</div>
