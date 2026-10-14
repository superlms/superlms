<div class="min-h-screen bg-gray-50">

    @if (!$aboutApp)
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Information Available</h3>
            <p class="text-gray-400 text-sm max-w-sm">App information has not been configured yet.</p>
        </div>
    @else
        {{-- ══════════════════════════════════════════════════
             HEADER — as the Super Admin's view: the heading with its sub
             heading beside it, and the last update as plain text
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="flex items-center gap-3 min-w-0">
                    <x-admin.back-to-more />
                    <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $aboutApp->heading ?? 'About App' }}</h1>
                        <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ $aboutApp->sub_heading ?? 'Platform application details' }}</p>
                    </div>
                </div>
                @if ($aboutApp->updated_at)
                    <span class="flex-shrink-0 text-xs text-gray-400">
                        Updated {{ $aboutApp->updated_at->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- In the Super Admin view's look: each part a white card on the grey
             page with its name and count, a quiet header row and a row per
             entry — the content sections (as the policy pages), contact
             details with the address, the core team, social links and
             documents. --}}
        @php
            $avCard = 'bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden';
            $avHead = 'px-5 py-3.5 border-b border-gray-100 flex items-baseline justify-between gap-3';
            $avTh   = 'px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider';
            $avThC  = 'px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider';
            $avEye  = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>';
        @endphp
        <div class="p-4 sm:p-6 space-y-5">

            {{-- Content Sections --}}
            @if (!empty($aboutApp->content))
                @include('livewire.super-admin.partials.sections-view', [
                    'sections' => collect($aboutApp->content ?? [])->map(fn ($s) => ['head' => $s['title'] ?? '', 'desc' => $s['description'] ?? ''])->all(),
                    'title'    => 'Content',
                ])
            @endif

            {{-- Contact Details, the address with them (an email or phone opens to write / call) --}}
            @if (!empty($aboutApp->contact_details) || $aboutApp->address)
                <div class="{{ $avCard }}">
                    <div class="{{ $avHead }}">
                        <h2 class="text-sm font-semibold text-gray-800">Contact Details</h2>
                        <span class="text-xs text-gray-400">{{ count($aboutApp->contact_details ?? []) + ($aboutApp->address ? 1 : 0) }} entries</span>
                    </div>
                    <table class="w-full table-fixed min-w-0!">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="{{ $avTh }} w-28 sm:w-40">Type</th>
                                <th class="{{ $avTh }}">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($aboutApp->contact_details ?? [] as $contact)
                                @php $type = strtolower($contact['type'] ?? ''); @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-500 truncate">{{ $contact['type'] ?? '' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 break-words">
                                        @if ($type === 'email')
                                            <a href="mailto:{{ $contact['value'] ?? '' }}" class="hover:text-blue-600 hover:underline">{{ $contact['value'] ?? '' }}</a>
                                        @elseif ($type === 'phone')
                                            <a href="tel:{{ $contact['value'] ?? '' }}" class="hover:text-blue-600 hover:underline">{{ $contact['value'] ?? '' }}</a>
                                        @else
                                            {{ $contact['value'] ?? '' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if ($aboutApp->address)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-500">Address</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 break-words">{{ $aboutApp->address }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Core Team --}}
            @if (!empty($aboutApp->core_team))
                <div class="{{ $avCard }}">
                    <div class="{{ $avHead }}">
                        <h2 class="text-sm font-semibold text-gray-800">Our Core Team</h2>
                        <span class="text-xs text-gray-400">{{ count($aboutApp->core_team) }} members</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $avTh }} w-16">S.No</th>
                                    <th class="{{ $avTh }}">Member</th>
                                    <th class="{{ $avTh }} w-48">Position</th>
                                    <th class="{{ $avThC }} w-32">Profile</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($aboutApp->core_team as $i => $member)
                                    @php $memberUrl = $member['url'] ?? $member['link'] ?? null; @endphp
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                @if (!empty($member['image']))
                                                    <img src="{{ $member['image'] }}" alt="" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                                @else
                                                    <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($member['name'] ?? 'M', 0, 1)) }}</span>
                                                    </div>
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $member['name'] ?? '' }}</p>
                                                    @if (!empty($member['description']))
                                                        <p class="text-xs text-gray-400 truncate" title="{{ $member['description'] }}">{{ $member['description'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 truncate">{{ $member['position'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($memberUrl)
                                                <a href="{{ $memberUrl }}" target="_blank" rel="noopener noreferrer" class="text-xs font-medium text-blue-600 hover:underline">View Profile ↗</a>
                                            @else
                                                <span class="text-sm text-gray-300">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Social Media --}}
            @if (!empty($aboutApp->social_media))
                <div class="{{ $avCard }}">
                    <div class="{{ $avHead }}">
                        <h2 class="text-sm font-semibold text-gray-800">Follow Us On Social Media</h2>
                        <span class="text-xs text-gray-400">{{ count($aboutApp->social_media) }} links</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $avTh }} w-16">S.No</th>
                                    <th class="{{ $avTh }} w-48">Platform</th>
                                    <th class="{{ $avTh }}">Link</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($aboutApp->social_media as $i => $social)
                                    @php
                                        $platform = $social['platform'] ?? $social['name'] ?? 'Link';
                                        $url      = $social['url'] ?? '#';
                                    @endphp
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-6 h-6 flex items-center justify-center flex-shrink-0">
                                                    @if (!empty($social['icon']))
                                                        <img src="{{ $social['icon'] }}" alt="" class="w-full h-full object-contain">
                                                    @else
                                                        <x-social-platform-icon :platform="$platform" class="w-full h-full block" />
                                                    @endif
                                                </span>
                                                <span class="text-sm font-medium text-gray-900 capitalize truncate">{{ $platform }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                                class="block text-sm text-blue-600 hover:underline truncate" title="{{ $url }}">{{ $url }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Documents --}}
            @if (!empty($aboutApp->documents))
                <div class="{{ $avCard }}">
                    <div class="{{ $avHead }}">
                        <h2 class="text-sm font-semibold text-gray-800">Documents</h2>
                        <span class="text-xs text-gray-400">{{ count($aboutApp->documents) }} file(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] table-fixed">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $avTh }} w-16">S.No</th>
                                    <th class="{{ $avTh }}">Document</th>
                                    <th class="{{ $avTh }} w-28">Size</th>
                                    <th class="{{ $avThC }} w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($aboutApp->documents as $i => $doc)
                                    @php
                                        $ext = strtolower($doc['file_type'] ?? '');
                                        $extTile = match (true) {
                                            $ext === 'pdf'                 => 'bg-red-50 text-red-600',
                                            in_array($ext, ['doc', 'docx']) => 'bg-blue-50 text-blue-600',
                                            default                        => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="w-9 h-9 rounded-lg {{ $extTile }} flex flex-col items-center justify-center flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    <span class="text-[8px] font-bold leading-none mt-0.5 uppercase">{{ $doc['file_type'] ?? 'FILE' }}</span>
                                                </span>
                                                <span class="text-sm font-semibold text-gray-900 truncate" title="{{ $doc['title'] ?? '' }}">{{ $doc['title'] ?? 'Document' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ !empty($doc['file_size']) ? number_format($doc['file_size'] / 1024, 0) . ' KB' : '—' }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center">
                                                @if (!empty($doc['file_path']))
                                                    <a href="{{ $doc['file_path'] }}" target="_blank" rel="noopener noreferrer" title="View"
                                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">{!! $avEye !!}</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    @endif

</div>
