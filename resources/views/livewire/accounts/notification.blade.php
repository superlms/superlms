<div class="min-h-screen bg-gray-50/50">

    {{-- ═══ STICKY HEADER ═══ --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 pt-4 pb-3 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-emerald-700 leading-tight">Send Notification</h1>
                <p class="text-xs text-gray-400 mt-0.5">Send push notifications to students and teachers</p>
            </div>
        </div>

        {{-- Analytics Strip --}}
        <div class="px-6 pb-3 grid grid-cols-3 gap-3">
            <div class="flex items-center gap-3 bg-emerald-50 rounded-xl px-4 py-3 border border-emerald-100">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="users" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total Students</p>
                    <p class="text-lg font-bold text-emerald-700">{{ $totalStudents }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-blue-50 rounded-xl px-4 py-3 border border-blue-100">
                <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="academic-cap" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total Teachers</p>
                    <p class="text-lg font-bold text-blue-700">{{ $totalTeachers }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-purple-50 rounded-xl px-4 py-3 border border-purple-100">
                <div class="w-8 h-8 rounded-lg bg-purple-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="device-phone-mobile" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Registered Devices</p>
                    <p class="text-lg font-bold text-purple-700">{{ $registeredDevices }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm flex items-center gap-2 mb-5">
                <x-icon name="check-circle" class="w-4 h-4 text-green-500" />
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm flex items-center gap-2 mb-5">
                <x-icon name="exclamation-circle" class="w-4 h-4 text-red-500" />
                {{ session('error') }}
            </div>
        @endif

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Compose Notification</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Fill in the details and choose recipients</p>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Recipients --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Send To <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach([
                                ['all_students', 'All Students', 'users'],
                                ['all_teachers', 'All Teachers', 'academic-cap'],
                                ['all', 'Everyone', 'globe-alt'],
                                ['by_class', 'Specific Class', 'building-library'],
                            ] as [$val, $label, $icon])
                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition-colors
                                {{ $targetType === $val ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="targetType" value="{{ $val }}" class="sr-only" />
                                <div class="w-7 h-7 rounded-lg {{ $targetType === $val ? 'bg-emerald-500' : 'bg-gray-100' }} flex items-center justify-center flex-shrink-0">
                                    <x-icon name="{{ $icon }}" class="w-3.5 h-3.5 {{ $targetType === $val ? 'text-white' : 'text-gray-400' }}" />
                                </div>
                                <span class="text-sm font-medium {{ $targetType === $val ? 'text-emerald-700' : 'text-gray-600' }}">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('targetType') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Class Select (when by_class) --}}
                    @if($targetType === 'by_class')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Class <span class="text-red-500">*</span></label>
                        <select wire:model="targetClass"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                            <option value="">Choose a class…</option>
                            @foreach($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                        @error('targetClass') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notification Title <span class="text-red-500">*</span></label>
                        <input wire:model="title" type="text" maxlength="255"
                            placeholder="e.g. Fee Reminder"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none" />
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Body --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
                        <textarea wire:model="body" rows="4" maxlength="1000"
                            placeholder="Write your notification message here…"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none resize-none"></textarea>
                        <p class="text-xs text-gray-400 mt-1 text-right">{{ strlen($body) }}/1000</p>
                        @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Info Notice --}}
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs text-blue-700 flex items-start gap-2">
                        <x-icon name="information-circle" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        <span>Notifications will only reach users who have the mobile app installed and have allowed notifications. Only devices with registered FCM tokens will receive the push notification.</span>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                    <button wire:click="send" wire:loading.attr="disabled"
                        class="px-6 py-2.5 text-sm font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="send">
                            <x-icon name="paper-airplane" class="w-4 h-4 inline" />
                        </span>
                        <span wire:loading wire:target="send">
                            <svg class="animate-spin w-4 h-4 inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="send">Send Notification</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
