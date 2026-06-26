<div class="max-w-7xl mx-auto p-6">
    <!-- Header with Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Home</h1>
            <p class="text-gray-500 mt-1">Welcome back! Here's what's happening today</p>
        </div>
        <div class="w-full md:w-96">
            <div class="relative">
                <input type="text" wire:model.live="searchQuery"
                    placeholder="Search features (e.g. 'attendance', 'timetable')"
                    class="w-full p-3 pl-10 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm">
                <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    @if ($searchQuery && count($searchResults) > 0)
        <div class="relative z-10 mb-4">
            <div
                class="absolute top-0 left-0 right-0 bg-white rounded-lg shadow-xl border border-gray-200 max-w-2xl mx-auto">
                @foreach ($searchResults as $route => $label)
                    <div wire:click="selectResult('{{ $route }}')"
                        class="p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                        <div class="font-medium text-gray-800">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($searchQuery && empty($searchResults))
        <div
            class="mb-4 p-4 bg-white rounded-lg shadow border border-gray-200 text-center text-gray-500 max-w-2xl mx-auto">
            No results found for "{{ $searchQuery }}"
        </div>
    @endif

    <!-- Top Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Students -->
        <a href="{{ route('admin.student', ['organization' => $organization]) }}"
            class="block bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Students</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalStudents) }}</h3>
                    <div class="mt-3 flex gap-4 text-sm">
                        <span class="text-blue-100">Active: {{ $activeStudents }}</span>
                        <span class="text-blue-100">Inactive: {{ $inactiveStudents }}</span>
                    </div>
                </div>
                <div class="bg-blue-400 bg-opacity-30 p-3 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- Total Teachers -->
        <a href="{{ route('admin.teacher', ['organization' => $organization]) }}"
            class="block bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Teachers</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalTeachers) }}</h3>
                    <div class="mt-3 flex gap-4 text-sm">
                        <span class="text-green-100">Active: {{ $activeTeachers }}</span>
                        <span class="text-green-100">Inactive: {{ $inactiveTeachers }}</span>
                    </div>
                </div>
                <div class="bg-green-400 bg-opacity-30 p-3 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- Total Classes -->
        <a href="{{ route('admin.standard', ['organization' => $organization]) }}"
            class="block bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Classes</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalClasses) }}</h3>
                    <p class="mt-3 text-sm text-purple-100">Classes</p>
                </div>
                <div class="bg-purple-400 bg-opacity-30 p-3 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </a>

        <!-- Total Subjects -->
        <a href="{{ route('admin.standard', ['organization' => $organization]) }}"
            class="block bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Total Subjects</p>
                    <h3 class="text-3xl font-bold mt-2">{{ number_format($totalSubjects) }}</h3>
                    <p class="mt-3 text-sm text-orange-100">Across All Classes</p>
                </div>
                <div class="bg-orange-400 bg-opacity-30 p-3 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Quick Actions Card -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg">Quick Actions</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4">
                    <a href="{{ route('admin.attendance', ['organization' => auth()->user()->organization]) }}"
                        class="flex flex-col items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-center">Mark Attendance</span>
                    </a>
                    <a href="{{ route('admin.announcement', ['organization' => auth()->user()->organization]) }}"
                        class="flex flex-col items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-center">New Announcement</span>
                    </a>
                    <a href="{{ route('admin.arrangement', ['organization' => auth()->user()->organization]) }}"
                        class="flex flex-col items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 hover:shadow-md transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-center">Arrangements</span>
                    </a>
                </div>
            </div>

            <!-- Students Statistics - Last 7 Days -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                        Student Attendance - Last 7 Days
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm text-gray-600">Total Students</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($totalStudents) }}</p>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <p class="text-sm text-gray-600">Present Today</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($studentsPresentToday) }}
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-50 rounded-lg">
                            <p class="text-sm text-gray-600">Absent Today</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($studentsAbsentToday) }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600">Day</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600">Present</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600">Absent</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($last7DaysData as $data)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium">{{ $data['day'] }}</td>
                                        <td class="px-4 py-3 text-center text-green-600 font-medium">
                                            {{ $data['student_present'] }}</td>
                                        <td class="px-4 py-3 text-center text-red-600 font-medium">
                                            {{ $data['student_absent'] }}</td>
                                        <td class="px-4 py-3 text-center text-gray-700">{{ $data['student_total'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Teachers Statistics - Last 7 Days -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        Teacher Attendance - Last 7 Days
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <p class="text-sm text-gray-600">Total Teachers</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($totalTeachers) }}</p>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <p class="text-sm text-gray-600">Present Today</p>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($teachersPresentToday) }}
                            </p>
                        </div>
                        <div class="text-center p-3 bg-red-50 rounded-lg">
                            <p class="text-sm text-gray-600">Absent Today</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($teachersAbsentToday) }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600">Day</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600">Present</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600">Absent</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($last7DaysData as $data)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium">{{ $data['day'] }}</td>
                                        <td class="px-4 py-3 text-center text-green-600 font-medium">
                                            {{ $data['teacher_present'] }}</td>
                                        <td class="px-4 py-3 text-center text-red-600 font-medium">
                                            {{ $data['teacher_absent'] }}</td>
                                        <td class="px-4 py-3 text-center text-gray-700">{{ $data['teacher_total'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Fee Statistics - Last 7 Days -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        Fee Collection - Last 7 Days
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-gray-600">Total Fee</p>
                            <p class="text-lg font-bold text-blue-600">₹{{ number_format($totalFee, 2) }}</p>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <p class="text-xs text-gray-600">Collected Today</p>
                            <p class="text-lg font-bold text-green-600">₹{{ number_format($feeCollectedToday, 2) }}
                            </p>
                        </div>
                        <div class="text-center p-3 bg-purple-50 rounded-lg">
                            <p class="text-xs text-gray-600">Overall Collected</p>
                            <p class="text-lg font-bold text-purple-600">₹{{ number_format($overallFeeCollected, 2) }}
                            </p>
                        </div>
                        <div class="text-center p-3 bg-orange-50 rounded-lg">
                            <p class="text-xs text-gray-600">Fee Remaining</p>
                            <p class="text-lg font-bold text-orange-600">₹{{ number_format($feeRemaining, 2) }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600">Day</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-600">Amount Collected</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($last7DaysData as $data)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium">{{ $data['day'] }}</td>
                                        <td class="px-4 py-3 text-right text-green-600 font-medium">
                                            ₹{{ number_format($data['fee_collected'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Searches -->
            @if (count($recentSearches) > 0)
                <div class="bg-white rounded-lg shadow border border-gray-200">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="font-semibold text-lg">Recent Searches</h3>
                        <button wire:click="clearRecentSearches" class="text-sm text-red-500 hover:text-red-700">
                            Clear All
                        </button>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach ($recentSearches as $search)
                            <div wire:click="$set('searchQuery', '{{ $search['term'] }}')"
                                class="p-4 hover:bg-gray-50 cursor-pointer flex justify-between items-center">
                                <div>
                                    <div class="font-medium text-gray-800">{{ $search['term'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $search['time']->diffForHumans() }}</div>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column (1/3 width) -->
        <div class="space-y-6">
            <!-- Upcoming Events -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg">Upcoming Events</h3>
                </div>
                <div class="p-4">
                    @if (count($upcomingEvents) > 0)
                        <div class="relative">
                            <div class="border-l-2 border-gray-200 absolute h-full left-4 top-0"></div>
                            @foreach ($upcomingEvents as $event)
                                <div class="mb-6 ml-8 relative">
                                    <div
                                        class="absolute w-3 h-3 bg-{{ $event['color'] }}-500 rounded-full -left-5 top-1 border-2 border-white">
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex justify-between items-start mb-1">
                                            <p class="font-medium text-gray-800">{{ $event['title'] }}</p>
                                            <span
                                                class="bg-{{ $event['color'] }}-100 text-{{ $event['color'] }}-800 text-xs px-2 py-1 rounded-full whitespace-nowrap ml-2">
                                                {{ $event['formatted_date'] }}
                                            </span>
                                        </div>
                                        @if ($event['description'])
                                            <p class="text-sm text-gray-500 mb-1">
                                                {{ Str::limit($event['description'], 50) }}</p>
                                        @endif
                                        <div class="flex flex-wrap gap-2 text-xs text-gray-600">
                                            @if ($event['is_all_day'])
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    All Day
                                                </span>
                                            @elseif($event['start_time'])
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ $event['start_time'] }}
                                                    @if ($event['end_time'])
                                                        - {{ $event['end_time'] }}
                                                    @endif
                                                </span>
                                            @endif
                                            @if ($event['location'])
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ Str::limit($event['location'], 20) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-8">No upcoming events</p>
                    @endif
                    <div class="text-center mt-4">
                        <a href="{{ route('admin.calender', ['organization' => auth()->user()->organization]) }}"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Full Calendar</a>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary Chart -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg">Today's Attendance</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-600">Students</span>
                            <span class="text-sm font-bold">
                                {{ $studentsPresentToday > 0 && $totalStudents > 0 ? number_format(($studentsPresentToday / $totalStudents) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full"
                                style="width: {{ $totalStudents > 0 ? ($studentsPresentToday / $totalStudents) * 100 : 0 }}%">
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Present: {{ $studentsPresentToday }}</span>
                            <span>Absent: {{ $studentsAbsentToday }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-600">Teachers</span>
                            <span class="text-sm font-bold">
                                {{ $teachersPresentToday > 0 && $totalTeachers > 0 ? number_format(($teachersPresentToday / $totalTeachers) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full"
                                style="width: {{ $totalTeachers > 0 ? ($teachersPresentToday / $totalTeachers) * 100 : 0 }}%">
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Present: {{ $teachersPresentToday }}</span>
                            <span>Absent: {{ $teachersAbsentToday }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Summary -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg">Fee Overview</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span class="text-sm text-gray-600">Collected</span>
                        <span class="font-bold text-green-600">₹{{ number_format($overallFeeCollected) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                        <span class="text-sm text-gray-600">Remaining</span>
                        <span class="font-bold text-orange-600">₹{{ number_format($feeRemaining) }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <span class="text-sm text-gray-600">Total Expected</span>
                        <span class="font-bold text-blue-600">₹{{ number_format($totalFee) }}</span>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-600">Collection Progress</span>
                            <span class="text-sm font-bold">
                                {{ $totalFee > 0 ? number_format(($overallFeeCollected / $totalFee) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full"
                                style="width: {{ $totalFee > 0 ? ($overallFeeCollected / $totalFee) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg">Quick Links</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('admin.student', ['organization' => auth()->user()->organization]) }}"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                        <span class="text-sm text-gray-700">View All Students</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                    <a href="{{ route('admin.teacher', ['organization' => auth()->user()->organization]) }}"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                        <span class="text-sm text-gray-700">View All Teachers</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                    <a href="{{ route('admin.standard', ['organization' => auth()->user()->organization]) }}"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                        <span class="text-sm text-gray-700">Manage Classes</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                    <a href="{{ route('admin.analytics', ['organization' => auth()->user()->organization]) }}"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                        <span class="text-sm text-gray-700">View Analytics</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-semibold text-lg">Queries</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span class="text-sm text-gray-600">Student Queries Total</span>
                        <span class="font-bold text-green-600">{{ $studentQueries }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                        <span class="text-sm text-gray-600">Teacher Queries Total</span>
                        <span class="font-bold text-orange-600">{{ $teacherQueries }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                        <span class="text-sm text-gray-600">Website Queries Total</span>
                        <span class="font-bold text-blue-600">{{ $websiteQueries }}</span>
                    </div>
                </div>
            </div>

            <!-- Latest LMS Rating -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg">LMS Rating</h3>
                        <div class="flex items-center">
                            <span class="text-sm text-gray-600 mr-2">Average:</span>
                            <span class="font-bold text-yellow-600">{{ number_format($averageRating, 1) }}/5</span>
                        </div>
                    </div>
                </div>

                @if ($latestRating)
                    <div class="p-4 space-y-3">
                        <!-- Latest Rating -->
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-600">Latest Feedback</span>
                                    <div class="flex items-center mt-1">
                                        <div class="flex">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= $latestRating->rating)
                                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-gray-300" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                @endif
                                            @endfor
                                        </div>
                                        <span class="ml-2 text-sm font-semibold">{{ $latestRating->rating }}/5</span>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($latestRating->created_at)->format('d M, Y') }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 mt-2 line-clamp-2">{{ $latestRating->feedback }}</p>

                            @if ($latestRating->status === 'pending')
                                <div class="mt-3 flex items-center">
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pending Review
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- View All Button -->
                        <div class="pt-2">
                            <a href="{{ route('admin.rate-lms', ['organization' => auth()->user()->organization]) }}"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 transition-all duration-200">
                                <span>View Rating</span>
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="p-6 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 mb-3">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-1">No Ratings Yet</h4>
                        <p class="text-sm text-gray-500 mb-4">Be the first to rate our LMS platform</p>
                        <a href="{{ route('admin.rate-lms', ['organization' => auth()->user()->organization]) }}"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 transition-all duration-200">
                            <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            Rate LMS
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
