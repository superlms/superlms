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
    // A header cell as Mark Attendance's list has them; w-px keeps a column
    // as narrow as what is in it.
    $th      = 'px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap';
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

    <div>
        @switch($b['source'])
            @case('classes')
                @php $classes = $lms->classes(); @endphp
                @if ($classes)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $th }} w-px">S.No</th>
                                    <th class="{{ $th }} w-px">Class</th>
                                    <th class="{{ $th }} w-px">Sections</th>
                                    <th class="{{ $th }}">Subjects</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($classes as $i => $k)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-900 whitespace-nowrap">{{ $k['name'] }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ implode(', ', $k['sections']) ?: '—' }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ implode(', ', $k['subjects']) ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-4 py-3 text-sm text-gray-500">No classes set up yet — add them on {{ $where }} and they show on your website by themselves.</p>
                @endif
                @break

            @case('fees')
                @php
                    $fees     = $lms->fees();
                    $withYear = (bool) array_filter(array_column($fees, 'year'));
                @endphp
                @if ($fees)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $th }} w-px">S.No</th>
                                    <th class="{{ $th }} w-px">Class</th>
                                    <th class="{{ $th }} w-px">Section</th>
                                    @if ($withYear)<th class="{{ $th }} w-px">Session</th>@endif
                                    <th class="{{ $th }}">Particulars</th>
                                    <th class="{{ $th }} w-px !text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($fees as $i => $g)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-900 whitespace-nowrap">{{ $g['class'] }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $g['section'] ?: 'All Sections' }}</td>
                                        @if ($withYear)<td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $g['year'] ?: '—' }}</td>@endif
                                        <td class="px-4 py-2 text-gray-600">{{ implode(' · ', array_map(fn ($r) => $r['name'] . ' ' . SchoolSiteData::money($r['amount']), $g['rows'])) }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ SchoolSiteData::money($g['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-4 py-3 text-sm text-gray-500">No fee structure yet — set it on {{ $where }} and it shows on your website by itself.</p>
                @endif
                @break

            @case('rules')
                @php $rules = $lms->rules(); @endphp
                <div class="px-4 py-3">
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
                </div>
                @break

            @case('transport')
                @php $routes = $lms->transport(); @endphp
                @if ($routes)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $th }} w-px">S.No</th>
                                    <th class="{{ $th }}">Route</th>
                                    <th class="{{ $th }}">Pickup</th>
                                    <th class="{{ $th }}">Drop</th>
                                    <th class="{{ $th }} !text-right">Monthly</th>
                                    <th class="{{ $th }} !text-right">Annual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($routes as $i => $r)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-900">{{ $r['name'] }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $r['pickup'] ?: '—' }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $r['drop'] ?: '—' }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700 tabular-nums whitespace-nowrap">{{ SchoolSiteData::money($r['monthly']) }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ SchoolSiteData::money($r['annual']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-4 py-3 text-sm text-gray-500">No active routes yet — add them on {{ $where }} and they show on your website by themselves.</p>
                @endif
                @break

            @case('events')
                @php
                    $events = $lms->events();
                    [, , $session] = SchoolSiteData::session();
                @endphp
                @if ($events)
                    <p class="px-4 pt-3 text-xs text-gray-500">Session {{ $session }} · {{ count($events) }} {{ \Illuminate\Support\Str::plural('event', count($events)) }}</p>
                    <div class="overflow-x-auto mt-2 max-h-80 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-y border-gray-200">
                                <tr>
                                    <th class="{{ $th }} w-px">S.No</th>
                                    <th class="{{ $th }} w-px">Date</th>
                                    <th class="{{ $th }}">Event</th>
                                    <th class="{{ $th }} w-px">Type</th>
                                    <th class="{{ $th }} w-px">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($events as $i => $e)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 font-medium">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($e['date'])->format('d M Y, D') }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-900">{{ $e['title'] }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $e['type'] }}</td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $e['time'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="px-4 py-3 text-sm text-gray-500">No events this session yet — add them on {{ $where }} and they show on your website by themselves.</p>
                @endif
                @break
        @endswitch
    </div>
</div>
