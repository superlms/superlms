<div class="min-h-screen bg-gray-50"
    x-data
    x-on:open-list-pdf.window="window.open($event.detail.url, '_blank')">
    @php
        // Static color classes so Tailwind keeps them (no dynamic string building).
        $colorMap = [
            'blue'    => ['bg' => 'bg-blue-50',    'text' => 'text-blue-600',    'border' => 'hover:border-blue-300'],
            'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-600',  'border' => 'hover:border-indigo-300'],
            'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'hover:border-emerald-300'],
            'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600',   'border' => 'hover:border-amber-300'],
            'purple'  => ['bg' => 'bg-purple-50',  'text' => 'text-purple-600',  'border' => 'hover:border-purple-300'],
            'rose'    => ['bg' => 'bg-rose-50',     'text' => 'text-rose-600',    'border' => 'hover:border-rose-300'],
            'teal'    => ['bg' => 'bg-teal-50',     'text' => 'text-teal-600',    'border' => 'hover:border-teal-300'],
            'cyan'    => ['bg' => 'bg-cyan-50',     'text' => 'text-cyan-600',    'border' => 'hover:border-cyan-300'],
            'orange'  => ['bg' => 'bg-orange-50',   'text' => 'text-orange-600',  'border' => 'hover:border-orange-300'],
        ];
        $activeDef = $type ? ($definitions[$type] ?? null) : null;
    @endphp

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-30">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <x-admin.back-to-more />
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Lists</h1>
                </div>
            </div>
            <button wire:click="openPanel"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Create List
            </button>
        </div>
    </div>

    {{-- ─── Landing: quick-start type cards ─────────────────── --}}
    <div class="p-4 sm:p-6">
        <p class="text-sm text-gray-500 mb-4">Choose what you want to list:</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach ($definitions as $key => $def)
                @php $c = $colorMap[$def['color']] ?? $colorMap['blue']; @endphp
                <button wire:click="startWith('{{ $key }}')"
                    class="group text-left bg-white rounded-xl border border-gray-200 {{ $c['border'] }} hover:shadow-md transition-all p-4 flex items-start gap-3">
                    <div class="w-11 h-11 rounded-lg {{ $c['bg'] }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $def['icon'] }}" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $def['label'] }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5 leading-snug">{{ $def['desc'] }}</p>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         CREATE-LIST SLIDE-IN PANEL
    ══════════════════════════════════════════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">

                {{-- Panel header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        @if ($step === 2)
                            <button wire:click="backToTypes" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                        @endif
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ $step === 1 ? 'Create List' : ($activeDef['label'] ?? '') . ' List' }}
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $step === 1 ? 'Step 1 — choose the type of list' : 'Step 2 — filters, columns & options' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closePanel" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- ── STEP 1: type picker ── --}}
                @if ($step === 1)
                    <div class="flex-1 overflow-y-auto px-6 py-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($definitions as $key => $def)
                                @php $c = $colorMap[$def['color']] ?? $colorMap['blue']; @endphp
                                <button wire:click="selectType('{{ $key }}')"
                                    class="text-left bg-white rounded-lg border border-gray-200 {{ $c['border'] }} hover:shadow-sm transition-all p-3.5 flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg {{ $c['bg'] }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 {{ $c['text'] }}" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $def['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ $def['label'] }}</h3>
                                        <p class="text-xs text-gray-500 mt-0.5 leading-snug">{{ $def['desc'] }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                {{-- ── STEP 2: configure ── --}}
                @elseif ($activeDef)
                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                        {{-- List title --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">List Title</label>
                            <input type="text" wire:model="title" placeholder="e.g. Class 10-A Roster"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        {{-- Filters (only those the type needs) --}}
                        @php $filters = $activeDef['filters']; @endphp
                        @if (!empty($filters))
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @if (isset($filters['exam']))
                                    <div class="{{ isset($filters['month']) ? '' : 'sm:col-span-2' }}">
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            Exam @if ($filters['exam'] === 'required')<span class="text-red-500">*</span>@endif
                                        </label>
                                        <select wire:model="examId" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-blue-500">
                                            <option value="">{{ $filters['exam'] === 'required' ? 'Select exam…' : 'All exams' }}</option>
                                            @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->academic_year }})</option>@endforeach
                                        </select>
                                        @error('examId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                @endif

                                @if (isset($filters['standard']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            Class @if ($filters['standard'] === 'required')<span class="text-red-500">*</span>@endif
                                        </label>
                                        <select wire:model.live="standardId" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-blue-500">
                                            <option value="">{{ $filters['standard'] === 'required' ? 'Select class…' : 'All classes' }}</option>
                                            @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                                        </select>
                                        @error('standardId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                @endif

                                @if (isset($filters['section']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                                        <select wire:model.live="sectionId" @disabled(!$standardId) class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                                            <option value="">All sections</option>
                                            @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                                        </select>
                                    </div>
                                @endif

                                @if (isset($filters['month']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                            Month @if ($filters['month'] === 'required')<span class="text-red-500">*</span>@endif
                                        </label>
                                        <input type="month" wire:model="month" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-1 focus:ring-blue-500">
                                        @error('month')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Column picker --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Columns to include</label>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" wire:click="toggleAllColumns(true)" class="text-blue-600 hover:text-blue-800 font-medium">Select all</button>
                                    <span class="text-gray-300">·</span>
                                    <button type="button" wire:click="toggleAllColumns(false)" class="text-gray-500 hover:text-gray-700 font-medium">Clear</button>
                                </div>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 max-h-64 overflow-y-auto">
                                @foreach ($activeDef['columns'] as $colKey => $colLabel)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" value="{{ $colKey }}" wire:model="selectedColumns" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        {{ $colLabel }}
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedColumns')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Blank columns + orientation --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Blank columns</label>
                                <input type="number" min="0" max="10" wire:model="blankColumns"
                                    class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-[11px] text-gray-400 mt-1">Empty columns for signatures, remarks, etc.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Page orientation</label>
                                <select wire:model="orientation" class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white focus:ring-1 focus:ring-blue-500">
                                    <option value="portrait">Portrait</option>
                                    <option value="landscape">Landscape</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
                        <span class="text-xs text-gray-400">{{ count($selectedColumns) }} column(s){{ $blankColumns > 0 ? ' + ' . $blankColumns . ' blank' : '' }}</span>
                        <div class="flex items-center gap-2">
                            <button wire:click="closePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                            <button wire:click="generate" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span wire:loading.remove wire:target="generate">Generate PDF</span>
                                <span wire:loading wire:target="generate">Generating…</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Generated PDF opens in a new tab via the root x-on:open-list-pdf handler. --}}
</div>
