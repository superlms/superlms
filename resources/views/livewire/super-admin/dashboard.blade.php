<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════════════
         STICKY HEADER
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-3 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900 truncate">Super Admin Dashboard</h1>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('super-admin.schools') }}"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H4" />
                    </svg>
                    Add School
                </a>
                <button wire:click="refreshData"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-gray-600 border border-gray-300 bg-white hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="p-3 sm:p-6 space-y-4 sm:space-y-6">

        {{-- ══════════════════════════════════════════════════════════
             TOP ANALYTICS STRIP
        ══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Total Schools --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Schools</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ number_format($totalSchools) }}</p>
                <p class="text-xs text-gray-500 mt-1">Total registered schools</p>
            </div>

            {{-- Total Students --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Students</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ number_format($totalStudents) }}</p>
                <p class="text-xs text-gray-500 mt-1">Total students enrolled</p>
            </div>

            {{-- Total Teachers --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full">Teachers</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ number_format($totalTeachers) }}</p>
                <p class="text-xs text-gray-500 mt-1">Total teachers across schools</p>
            </div>

            {{-- Avg Students/School --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Average</span>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900">{{ number_format($avgStudentsPerSchool) }}</p>
                <p class="text-xs text-gray-500 mt-1">Avg students per school</p>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             TOP SCHOOLS + RECENT SCHOOLS
        ══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Top 5 by Students --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Top Schools by Students</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Highest enrollment</p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-1 bg-blue-50 text-blue-600 rounded-full">Top 5</span>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse ($topSchoolsByStudents as $school)
                        <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50/60 transition-colors">
                            <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $school['rank'] === 1 ? 'bg-amber-100 text-amber-700' : ($school['rank'] === 2 ? 'bg-gray-100 text-gray-600' : 'bg-gray-50 text-gray-400') }}">
                                {{ $school['rank'] }}
                            </span>
                            @if ($school['logo'])
                                <img src="{{ $school['logo'] }}" alt="{{ $school['name'] }}"
                                    class="w-8 h-8 rounded-lg object-cover flex-shrink-0 border border-gray-100">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-blue-600">{{ strtoupper(substr($school['name'], 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $school['name'] }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-blue-600">{{ number_format($school['students_count']) }}</p>
                                <p class="text-xs text-gray-400">students</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400">No schools yet</div>
                    @endforelse
                </div>
            </div>

            {{-- Top 5 by Teachers --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Top Schools by Teachers</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Most teaching staff</p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-1 bg-violet-50 text-violet-600 rounded-full">Top 5</span>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse ($topSchoolsByTeachers as $school)
                        <div class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50/60 transition-colors">
                            <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $school['rank'] === 1 ? 'bg-amber-100 text-amber-700' : ($school['rank'] === 2 ? 'bg-gray-100 text-gray-600' : 'bg-gray-50 text-gray-400') }}">
                                {{ $school['rank'] }}
                            </span>
                            @if ($school['logo'])
                                <img src="{{ $school['logo'] }}" alt="{{ $school['name'] }}"
                                    class="w-8 h-8 rounded-lg object-cover flex-shrink-0 border border-gray-100">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-violet-600">{{ strtoupper(substr($school['name'], 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $school['name'] }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-violet-600">{{ number_format($school['teachers_count']) }}</p>
                                <p class="text-xs text-gray-400">teachers</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400">No schools yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             RECENT SCHOOLS
        ══════════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Recently Registered Schools</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Latest 6 schools on the platform</p>
                </div>
                <a href="{{ route('super-admin.schools') }}"
                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                    View all →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">School</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Teachers</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($recentSchools as $school)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if ($school['logo'])
                                            <img src="{{ $school['logo'] }}" alt="{{ $school['name'] }}"
                                                class="w-9 h-9 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-sm font-bold text-emerald-600">{{ strtoupper(substr($school['name'], 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <p class="text-sm font-semibold text-gray-800 max-w-[180px] truncate">{{ $school['name'] }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-sm font-bold text-blue-600">{{ number_format($school['students_count']) }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-sm font-bold text-violet-600">{{ number_format($school['teachers_count']) }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-xs text-gray-500">{{ $school['created_at'] }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if ($school['status'])
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <a href="{{ route('super-admin.schools') }}"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:text-emerald-800 px-3 py-1.5 border border-emerald-200 rounded-lg hover:bg-emerald-50 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">No schools registered yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             CHART + RECENT ACTIVITY
        ══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Monthly Registrations Chart --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Monthly Registrations</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Students & teachers over last 12 months</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Students
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-violet-500 inline-block"></span> Teachers
                        </span>
                    </div>
                </div>
                <div style="height: 240px; position: relative;">
                    <canvas id="dashMonthlyChart"></canvas>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Recent Activity</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Latest platform events</p>
                </div>
                <div class="divide-y divide-gray-50 overflow-y-auto" style="max-height: 290px;">
                    @forelse ($recentActivities as $activity)
                        <div class="px-4 py-3 flex items-start gap-3 hover:bg-gray-50/60 transition-colors">
                            <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center mt-0.5
                                {{ $activity['color'] === 'emerald' ? 'bg-emerald-100' : ($activity['color'] === 'blue' ? 'bg-blue-100' : 'bg-violet-100') }}">
                                @if ($activity['type'] === 'school')
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                @elseif ($activity['type'] === 'student')
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-700">{{ $activity['title'] }}</p>
                                <p class="text-xs text-gray-400 truncate mt-0.5">{{ $activity['description'] }}</p>
                                <p class="text-[10px] text-gray-300 mt-1">{{ $activity['time'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-gray-400">No recent activity</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             QUICK ACTIONS
        ══════════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="{{ route('super-admin.schools') }}"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 transition-colors group">
                    <div class="w-10 h-10 bg-emerald-500 group-hover:bg-emerald-600 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-emerald-700">Add School</span>
                </a>
                <a href="{{ route('super-admin.credit') }}"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-100 transition-colors group">
                    <div class="w-10 h-10 bg-blue-500 group-hover:bg-blue-600 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-blue-700">Credit</span>
                </a>
                <a href="{{ route('super-admin.payroll') }}"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl bg-violet-50 hover:bg-violet-100 border border-violet-100 transition-colors group">
                    <div class="w-10 h-10 bg-violet-500 group-hover:bg-violet-600 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-violet-700">Payroll</span>
                </a>
                <a href="{{ route('super-admin.enquiries') }}"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-100 transition-colors group">
                    <div class="w-10 h-10 bg-amber-500 group-hover:bg-amber-600 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-amber-700">Enquiries</span>
                </a>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             STATS ROW: RATING + SUPPORT + ENQUIRY + CREDIT
        ══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Rating Stats --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                    </div>
                    <a href="{{ route('super-admin.rating') }}" class="text-xs text-amber-600 font-semibold hover:underline">View →</a>
                </div>
                <p class="text-lg font-bold text-gray-900 mb-2">Ratings</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Avg Rating</span>
                        <span class="font-bold text-amber-600">{{ $ratingStats['avg_rating'] }} ★</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Rated Schools</span>
                        <span class="font-semibold text-gray-800">{{ $ratingStats['schools_rated'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pending</span>
                        <span class="font-semibold text-red-500">{{ $ratingStats['remaining'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Support Stats --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 bg-sky-50 rounded-xl flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <a href="{{ route('super-admin.support') }}" class="text-xs text-sky-600 font-semibold hover:underline">View →</a>
                </div>
                <p class="text-lg font-bold text-gray-900 mb-2">Support</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Tickets</span>
                        <span class="font-bold text-gray-800">{{ $supportStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pending</span>
                        <span class="font-semibold text-red-500">{{ $supportStats['pending'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">This Month</span>
                        <span class="font-semibold text-sky-600">{{ $supportStats['this_month'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Enquiry Stats --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 bg-violet-50 rounded-xl flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                    </div>
                    <a href="{{ route('super-admin.enquiries') }}" class="text-xs text-violet-600 font-semibold hover:underline">View →</a>
                </div>
                <p class="text-lg font-bold text-gray-900 mb-2">Enquiries</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Demo Requests</span>
                        <span class="font-bold text-gray-800">{{ $enquiryStats['demo_total'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Contact</span>
                        <span class="font-semibold text-gray-800">{{ $enquiryStats['contact_total'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pending</span>
                        <span class="font-semibold text-red-500">{{ $enquiryStats['demo_pending'] + $enquiryStats['contact_pending'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Credit Stats --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <a href="{{ route('super-admin.credit') }}" class="text-xs text-emerald-600 font-semibold hover:underline">View →</a>
                </div>
                <p class="text-lg font-bold text-gray-900 mb-2">Credit</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Amount</span>
                        <span class="font-bold text-emerald-600">₹{{ number_format($creditStats['total_amount_leased'], 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Approved</span>
                        <span class="font-semibold text-gray-800">{{ $creditStats['approved'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pending</span>
                        <span class="font-semibold text-amber-600">{{ $creditStats['pending'] }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════
         CHART INIT
    ══════════════════════════════════════════════════════════ --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('dashMonthlyChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($monthlyRegistrations['labels']),
                    datasets: [
                        {
                            label: 'Students',
                            data: @json($monthlyRegistrations['students']),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Teachers',
                            data: @json($monthlyRegistrations['teachers']),
                            backgroundColor: 'rgba(139, 92, 246, 0.7)',
                            borderRadius: 6,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 10,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color: '#9ca3af' }
                        },
                        y: {
                            grid: { color: '#f3f4f6' },
                            ticks: { font: { size: 10 }, color: '#9ca3af', stepSize: 1 },
                            beginAtZero: true,
                        }
                    }
                }
            });
        });
    </script>

</div>
