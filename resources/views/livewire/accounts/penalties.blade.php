<div class="min-h-screen bg-gray-50">

    {{-- Sticky Header --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 pt-4 pb-3">
            {{-- Title row --}}
            <div class="mb-3">
                <h1 class="text-xl font-bold text-gray-900 leading-tight">Penalties</h1>
                <p class="text-xs text-gray-500 mt-0.5">Configure late fee penalty rules and monitor overdue charges</p>
            </div>

            {{-- Analytics Strip --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                {{-- Estimated Total Penalty --}}
                <div class="bg-red-50 rounded-xl border border-red-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-red-500 uppercase tracking-wider leading-none mb-1">Est. Total Penalty</p>
                    <p class="text-sm font-bold text-red-600 leading-tight truncate">
                        ₹{{ number_format($penaltyAnalytics['total'] ?? 0, 2) }}
                    </p>
                    <p class="text-[10px] text-red-400 mt-0.5">Accumulated</p>
                </div>

                {{-- Overdue Students --}}
                <div class="bg-amber-50 rounded-xl border border-amber-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-amber-600 uppercase tracking-wider leading-none mb-1">Overdue Students</p>
                    <p class="text-lg font-bold text-amber-700 leading-tight">{{ $penaltyAnalytics['students'] ?? 0 }}</p>
                    <p class="text-[10px] text-amber-500 mt-0.5">Not paid this cycle</p>
                </div>

                {{-- Days Overdue --}}
                <div class="bg-orange-50 rounded-xl border border-orange-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-orange-600 uppercase tracking-wider leading-none mb-1">Days Overdue</p>
                    <p class="text-lg font-bold text-orange-700 leading-tight">{{ $penaltyAnalytics['days_overdue'] ?? 0 }}</p>
                    <p class="text-[10px] text-orange-500 mt-0.5">Since due date</p>
                </div>

                {{-- Penalty Per Day --}}
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 px-3 py-2.5">
                    <p class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider leading-none mb-1">Rate / Day</p>
                    <p class="text-sm font-bold text-emerald-700 leading-tight truncate">
                        ₹{{ number_format($penaltyAnalytics['penalty_per_day'] ?? 0, 2) }}
                    </p>
                    <p class="text-[10px] text-emerald-500 mt-0.5">Per student</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Page body --}}
    <div class="px-6 py-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Left: Settings Configuration --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Settings Configuration</h2>
                        <p class="text-xs text-gray-500">Set penalty rules for late payments</p>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-5">
                    {{-- Penalty Per Day --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Penalty Per Day
                            <span class="text-xs font-normal text-gray-400 ml-1">(INR)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-500">₹</span>
                            <input type="number" wire:model="penaltyPerDay" step="0.01" min="0"
                                class="w-full pl-7 rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        </div>
                        @error('penaltyPerDay')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">Amount charged per student per day after due date.</p>
                    </div>

                    {{-- Cycle Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Cycle Type</label>
                        <select wire:model="cycleType"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                        @error('cycleType')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Due Day of Month --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Day of Month</label>
                        <input type="number" wire:model="dueDayOfMonth" min="1" max="31"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('dueDayOfMonth')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">Day of the month when fee payment is due (1–31).</p>
                    </div>

                    {{-- Save Button --}}
                    <button wire:click="saveSettings"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Save Settings
                    </button>
                </div>
            </div>

            {{-- Right: Summary & Info --}}
            <div class="space-y-5">

                {{-- Current Penalty Summary --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800">Current Penalty Summary</h2>
                            <p class="text-xs text-gray-500">Live figures based on today's date</p>
                        </div>
                    </div>

                    <div class="px-6 py-5 grid grid-cols-2 gap-4">
                        <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                            <p class="text-xs font-medium text-red-500 uppercase tracking-wider">Estimated Total</p>
                            <p class="text-xl font-bold text-red-600 mt-1">
                                ₹{{ number_format($penaltyAnalytics['total'] ?? 0, 2) }}
                            </p>
                            <p class="text-[11px] text-red-400 mt-0.5">
                                {{ ($penaltyAnalytics['students'] ?? 0) }} students × {{ ($penaltyAnalytics['days_overdue'] ?? 0) }} days
                            </p>
                        </div>

                        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                            <p class="text-xs font-medium text-amber-600 uppercase tracking-wider">Overdue Students</p>
                            <p class="text-xl font-bold text-amber-700 mt-1">{{ $penaltyAnalytics['students'] ?? 0 }}</p>
                            <p class="text-[11px] text-amber-500 mt-0.5">Have not paid this cycle</p>
                        </div>

                        <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                            <p class="text-xs font-medium text-orange-600 uppercase tracking-wider">Days Overdue</p>
                            <p class="text-xl font-bold text-orange-700 mt-1">{{ $penaltyAnalytics['days_overdue'] ?? 0 }}</p>
                            <p class="text-[11px] text-orange-500 mt-0.5">Calendar days since due</p>
                        </div>

                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                            <p class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Rate / Day</p>
                            <p class="text-xl font-bold text-emerald-700 mt-1">
                                ₹{{ number_format($penaltyAnalytics['penalty_per_day'] ?? 0, 2) }}
                            </p>
                            <p class="text-[11px] text-emerald-500 mt-0.5">Per student per day</p>
                        </div>
                    </div>
                </div>

                {{-- How Penalties Work --}}
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800">How Penalties Work</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Penalty is calculated per day after the due date for students who have not paid their fee.
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                The due date is the
                                <span class="font-semibold text-gray-800">
                                    {{ $dueDayOfMonth }}{{ in_array($dueDayOfMonth % 10, [1]) && $dueDayOfMonth != 11 ? 'st' : (in_array($dueDayOfMonth % 10, [2]) && $dueDayOfMonth != 12 ? 'nd' : (in_array($dueDayOfMonth % 10, [3]) && $dueDayOfMonth != 13 ? 'rd' : 'th')) }}
                                </span>
                                of each
                                <span class="font-semibold text-gray-800">{{ ucfirst($cycleType) }}</span> cycle.
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Formula:
                                <span class="font-mono text-xs font-medium text-gray-800 bg-gray-100 px-1.5 py-0.5 rounded">
                                    Overdue Students × Days Overdue × Rate Per Day
                                </span>
                            </p>
                        </div>
                        @if(($penaltyAnalytics['total'] ?? 0) == 0 && ($penaltyAnalytics['penalty_per_day'] ?? 0) == 0)
                            <div class="mt-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                <p class="text-xs text-amber-700">Penalties are currently disabled. Set a rate above zero to activate.</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
