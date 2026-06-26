<div>
    {{-- ================================================================== --}}
    {{--  STICKY HEADER                                                      --}}
    {{-- ================================================================== --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        {{-- Title row + sub-tabs --}}
        <div class="px-6 pt-4 pb-0 flex items-end justify-between gap-4 flex-wrap">
            <div class="pb-3">
                <h1 class="text-xl font-bold text-emerald-700 leading-tight">View Fee</h1>
                <p class="text-xs text-gray-400 mt-0.5">View fee details by student or by class</p>
            </div>
            {{-- Sub-tab buttons --}}
            <div class="flex gap-1 pb-0">
                <button wire:click="setViewSubTab('by_student')"
                    class="px-5 py-2.5 text-sm font-semibold border-b-2 transition whitespace-nowrap
                        {{ $viewSubTab === 'by_student'
                            ? 'border-emerald-500 text-emerald-700'
                            : 'border-transparent text-gray-400 hover:text-emerald-600 hover:border-emerald-300' }}">
                    By Student
                </button>
                <button wire:click="setViewSubTab('by_class')"
                    class="px-5 py-2.5 text-sm font-semibold border-b-2 transition whitespace-nowrap
                        {{ $viewSubTab === 'by_class'
                            ? 'border-emerald-500 text-emerald-700'
                            : 'border-transparent text-gray-400 hover:text-emerald-600 hover:border-emerald-300' }}">
                    By Class
                </button>
            </div>
        </div>

        {{-- Analytics Strip --}}
        <div class="px-6 py-3 bg-gray-50/80 border-t border-gray-100 flex flex-wrap gap-3">
            <div class="flex items-center gap-3 bg-emerald-50 rounded-xl px-4 py-2.5 border border-emerald-100 flex-1 min-w-[130px]">
                <div>
                    <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wide">Total Fee</p>
                    <p class="text-sm font-bold text-emerald-800">₹{{ number_format($headerStats['totalFee'] ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-green-50 rounded-xl px-4 py-2.5 border border-green-100 flex-1 min-w-[130px]">
                <div>
                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide">Total Collected</p>
                    <p class="text-sm font-bold text-green-800">₹{{ number_format($headerStats['totalCollected'] ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-red-50 rounded-xl px-4 py-2.5 border border-red-100 flex-1 min-w-[130px]">
                <div>
                    <p class="text-[10px] font-semibold text-red-500 uppercase tracking-wide">Remaining</p>
                    <p class="text-sm font-bold text-red-700">₹{{ number_format($headerStats['remaining'] ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-orange-50 rounded-xl px-4 py-2.5 border border-orange-100 flex-1 min-w-[130px]">
                <div>
                    <p class="text-[10px] font-semibold text-orange-500 uppercase tracking-wide">Penalties</p>
                    <p class="text-sm font-bold text-orange-700">₹{{ number_format($headerStats['penalties'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{--  PAGE CONTENT                                                       --}}
    {{-- ================================================================== --}}
    <div class="p-6 space-y-5">

        {{-- ============================================================== --}}
        {{--  BY STUDENT TAB                                                 --}}
        {{-- ============================================================== --}}
        @if($viewSubTab === 'by_student')
            <div class="space-y-5">

                {{-- Filter Card --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Filter Student</p>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Class</label>
                            <select wire:model.live="viewStudentStandardId"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50">
                                <option value="">— Select Class —</option>
                                @foreach($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Section</label>
                            <select wire:model.live="viewStudentSectionId"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50"
                                @disabled(!$viewStudentStandardId)>
                                <option value="">All Sections</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Student</label>
                            <select wire:model.live="viewStudentId"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50"
                                @disabled(!$viewStudentStandardId)>
                                <option value="">— Select Student —</option>
                                @foreach($students as $stu)
                                    <option value="{{ $stu->id }}">
                                        {{ $stu->user->name ?? $stu->full_name }} ({{ $stu->admission_no }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button wire:click="loadStudentFeeView" wire:loading.attr="disabled"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700
                                       text-white font-semibold rounded-xl text-sm transition disabled:opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View Fee
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Student Fee Detail --}}
                @if(!empty($studentFeeView))
                    @include('livewire.accounts._partials.student-fee-view-card', ['data' => $studentFeeView])
                @else
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-14 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-500 mb-1">Select a Student</h3>
                        <p class="text-sm text-gray-400">Choose class, section, and student above then click "View Fee".</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- ============================================================== --}}
        {{--  BY CLASS TAB                                                   --}}
        {{-- ============================================================== --}}
        @if($viewSubTab === 'by_class')
            <div class="space-y-5">

                {{-- Filter Card --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Filter Class</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Class</label>
                            <select wire:model.live="viewClassStandardId"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50">
                                <option value="">— Select Class —</option>
                                @foreach($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Section</label>
                            <select wire:model.live="viewClassSectionId"
                                class="w-full rounded-xl border-gray-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-gray-50"
                                @disabled(!$viewClassStandardId)>
                                <option value="">All Sections</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button wire:click="loadClassFeeView" wire:loading.attr="disabled"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700
                                       text-white font-semibold rounded-xl text-sm transition disabled:opacity-60">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Load Class
                            </button>
                        </div>
                    </div>
                </div>

                {{-- When viewing a specific student from class list --}}
                @if($classViewStudentId && !empty($classStudentFeeView))
                    <div>
                        <button wire:click="backToClassList"
                            class="inline-flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-700 font-semibold mb-4 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Back to Class List
                        </button>
                        @include('livewire.accounts._partials.student-fee-view-card', ['data' => $classStudentFeeView])
                    </div>

                {{-- Class Fee List --}}
                @elseif(!empty($classFeeList))
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 border-b border-gray-50 flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Students</p>
                            <span class="text-xs text-gray-400">{{ count($classFeeList) }} record(s)</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-emerald-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Adm No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wide">Class-Sec</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Academic Fee</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Acad Paid</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Transport Fee</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Trans Paid</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Total</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-emerald-700 uppercase tracking-wide">Pending</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-emerald-700 uppercase tracking-wide">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-emerald-700 uppercase tracking-wide">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($classFeeList as $index => $item)
                                        @php
                                            $pending = $item['pending'] ?? 0;
                                            $totalCollected = $item['totalCollected'] ?? 0;
                                            $totalFeeRow = $item['totalFee'] ?? 0;
                                            if ($pending <= 0) {
                                                $statusClass = 'bg-emerald-100 text-emerald-700';
                                                $statusLabel = 'Paid';
                                            } elseif ($totalCollected > 0) {
                                                $statusClass = 'bg-amber-100 text-amber-700';
                                                $statusLabel = 'Partial';
                                            } else {
                                                $statusClass = 'bg-red-100 text-red-600';
                                                $statusLabel = 'Unpaid';
                                            }
                                        @endphp
                                        <tr class="hover:bg-emerald-50/40 transition">
                                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $item['name'] }}</td>
                                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $item['admission_no'] }}</td>
                                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $item['class_section'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-700 text-xs">₹{{ number_format($item['academicFee'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-emerald-600 font-medium text-xs">₹{{ number_format($item['academicCollected'], 2) }}</td>
                                            <td class="px-4 py-3 text-right text-gray-700 text-xs">
                                                @if($item['hasTransport'])
                                                    ₹{{ number_format($item['transportFee'], 2) }}
                                                @else
                                                    <span class="text-gray-300">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right text-xs">
                                                @if($item['hasTransport'])
                                                    <span class="text-blue-600 font-medium">₹{{ number_format($item['transportCollected'], 2) }}</span>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800 text-xs">₹{{ number_format($totalFeeRow, 2) }}</td>
                                            <td class="px-4 py-3 text-right text-xs {{ $pending > 0 ? 'text-red-500 font-semibold' : 'text-emerald-600' }}">
                                                ₹{{ number_format($pending, 2) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button wire:click="viewStudentFromClass({{ $item['id'] }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg
                                                           text-xs font-semibold border border-emerald-200 hover:bg-emerald-100 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                @else
                    {{-- Empty state for by_class --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-14 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-500 mb-1">Select a Class</h3>
                        <p class="text-sm text-gray-400">Choose a class and section, then click "Load Class" to see the fee list.</p>
                    </div>
                @endif
            </div>
        @endif

    </div>{{-- /page content --}}
</div>

{{-- ====================================================================== --}}
{{--  STUDENT FEE DETAIL PARTIAL (inline — used by both by_student and      --}}
{{--  by_class views via @include)                                           --}}
{{-- ====================================================================== --}}
@once
@push('styles'){{-- no extra styles needed --}}@endpush
@endonce
