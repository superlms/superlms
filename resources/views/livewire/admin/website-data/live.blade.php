{{--
    A live block of a Website Data page: what the site reads from the
    school's own records (App\Support\SchoolSiteData) — its classes with
    sections and subjects, its fee structure, its rules — shown as the site
    shows it now, with a link to where it is set up. Nothing to fill in here.
    Params: $b (with its source), $lms.
--}}
@php
    use App\Support\SchoolSiteData;
    use App\Support\WebsitePages;

    [, $where, $routeName] = WebsitePages::LIVE[$b['source']];
    $editUrl = \Illuminate\Support\Facades\Route::has($routeName) && $organization
        ? route($routeName, ['organization' => $organization->id]) : null;
    $chip    = 'inline-block px-2 py-0.5 rounded-full text-[11px] leading-4';
@endphp
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden" wire:key="blk-{{ $b['id'] }}">
    <div class="flex items-start gap-3 px-4 py-3 border-b border-gray-100">
        <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-800">
                {{ $b['label'] }}
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-semibold uppercase tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Live
                </span>
            </p>
            <p class="text-xs text-gray-500 mt-0.5">From your {{ $where }} — the website shows it as it is there, so change it there.</p>
        </div>
        @if ($editUrl)
            <a href="{{ $editUrl }}" class="text-xs font-medium text-blue-600 hover:text-blue-700 whitespace-nowrap">Open {{ $where }} &rarr;</a>
        @endif
    </div>

    <div class="px-4 py-3">
        @switch($b['source'])
            @case('classes')
                @php $classes = $lms->classes(); @endphp
                @forelse ($classes as $k)
                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1 py-1.5 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                        <span class="text-sm font-semibold text-gray-900 min-w-[5rem]">{{ $k['name'] }}</span>
                        @foreach ($k['sections'] as $s)
                            <span class="{{ $chip }} border border-gray-200 text-gray-600">{{ $s }}</span>
                        @endforeach
                        <span class="text-xs text-gray-400" title="{{ implode(', ', $k['subjects']) }}">
                            · {{ count($k['subjects']) }} {{ \Illuminate\Support\Str::plural('subject', count($k['subjects'])) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No classes set up yet — add them on {{ $where }} and they show on your website by themselves.</p>
                @endforelse
                @break

            @case('fees')
                @php $fees = $lms->fees(); @endphp
                @forelse ($fees as $g)
                    <div class="flex items-center gap-2 py-1.5 {{ ! $loop->last ? 'border-b border-gray-50' : '' }}">
                        <span class="text-sm font-semibold text-gray-900">{{ $g['class'] }}</span>
                        <span class="text-xs text-gray-400">{{ $g['section'] ?: 'All Sections' }}@if ($g['year'] !== '') · {{ $g['year'] }}@endif · {{ count($g['rows']) }} {{ \Illuminate\Support\Str::plural('particular', count($g['rows'])) }}</span>
                        <span class="ml-auto text-sm font-semibold text-gray-800 tabular-nums">{{ SchoolSiteData::money($g['total']) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No fee structure yet — set it on {{ $where }} and it shows on your website by itself.</p>
                @endforelse
                @break

            @case('rules')
                @php $rules = $lms->rules(); @endphp
                @if ($lms->hasRules())
                    <ol class="list-decimal pl-5 space-y-0.5 text-sm text-gray-800">
                        @foreach (array_slice($rules['sections'], 0, 6) as $s)
                            <li class="truncate">{{ $s['head'] ?: \Illuminate\Support\Str::limit($s['desc'], 80) }}</li>
                        @endforeach
                    </ol>
                    @php $more = count($rules['sections']) - 6; @endphp
                    <p class="text-xs text-gray-400 mt-1.5">
                        @if ($more > 0){{ $more }} more {{ \Illuminate\Support\Str::plural('rule', $more) }} · @endif
                        {{ count($rules['info']) }} additional {{ \Illuminate\Support\Str::plural('detail', count($rules['info'])) }} ·
                        {{ count($rules['files']) }} {{ \Illuminate\Support\Str::plural('PDF', count($rules['files'])) }}
                    </p>
                @else
                    <p class="text-sm text-gray-500">No rules saved yet — write them on {{ $where }} and they show on your website by themselves.</p>
                @endif
                @break
        @endswitch
    </div>
</div>
