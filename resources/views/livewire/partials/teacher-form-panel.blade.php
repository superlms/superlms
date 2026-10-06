{{-- The add / edit teacher slide-in panel (Exams style). Its own file so the
     Teachers page and Payroll's Add → Teacher (the Teacher component in its
     form-only mode) show the very same form. --}}
@if ($open)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

            {{-- Fixed header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Teacher' : 'New Teacher' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update teacher details' : 'Welcome email with login credentials will be sent on save' }}</p>
                </div>
                <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Scrollable body --}}
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                {{-- Inside Payroll's Add (the form-only mode): "who are you adding?"
                     stays on top of this form too, Teacher picked — another kind
                     picked there goes back to Payroll's own form. The Teachers page
                     itself has no such row. --}}
                @if (!empty($formOnly))
                    @include('livewire.partials.payroll-add-chooser', ['selected' => 'teacher', 'call' => '$parent.chooseEmpType'])
                @endif

                {{-- Profile image (single inline row, as on Students): the new photo,
                     else the saved one, else a placeholder — then the picker, which
                     lets the photo be fitted in its circle before it is uploaded --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Teacher Profile Image <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span></label>
                    <div class="flex items-center gap-3">
                        @if ($teacherImage)
                            <img src="{{ $teacherImage->temporaryUrl() }}"
                                class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @elseif ($editId && $teacherImageUrl)
                            <img src="{{ $teacherImageUrl }}"
                                class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        @endif
                        <x-admin.photo-editor model="teacherImage" class="flex-1 text-sm" />
                        @if (!$teacherImage && $editId && $teacherImageUrl)
                            {{-- The photo already saved, cropped again; saved with the form --}}
                            <button type="button" x-data title="Crop the saved photo"
                                x-on:click="$dispatch('lms-crop-photo', { model: 'teacherImage', url: @js(route('admin.teacher.photo', ['organization' => auth()->user()->organization_id, 'user' => $editId])) })"
                                class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 2v14a2 2 0 002 2h14M18 22V8a2 2 0 00-2-2H2" /></svg>
                                Crop
                            </button>
                        @endif
                    </div>
                    <div wire:loading wire:target="teacherImage" class="text-xs text-blue-600 mt-1">Uploading...</div>
                    @error('teacherImage')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Personal info grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <input wire:model.blur="teacherName" type="text" maxlength="50"
                            oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('teacherName')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input wire:model.defer="teacherEmail" type="email" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('teacherEmail')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    {{-- The name this teacher signs in with, and forgets a password by --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                        <input wire:model.blur="teacherUsername" type="text" maxlength="50" autocomplete="off" spellcheck="false"
                            oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9._@]/g,'')"
                            placeholder="e.g. meera@tds"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm font-mono focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('teacherUsername')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile <span class="text-red-500">*</span></label>
                        <input wire:model.defer="teacherMobile" type="tel" maxlength="10" inputmode="numeric"
                            oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('teacherMobile')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Employee ID <span class="text-red-500">*</span></label>
                        <input wire:model.defer="employeeId" type="text" maxlength="20" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('employeeId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                        <input wire:model.defer="dob" type="date" min="1940-01-01" max="{{ now()->subYear()->format('Y-m-d') }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('dob')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Joining</label>
                        <input wire:model.defer="dateOfJoining" type="date" min="1970-01-01" max="{{ now()->format('Y-m-d') }}" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('dateOfJoining')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                        <select wire:model.defer="teacherGender" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        @error('teacherGender')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Qualification <span class="text-red-500">*</span></label>
                        <input wire:model.defer="qualification" type="text" maxlength="50" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('qualification')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Emergency Contact</label>
                        <input wire:model.defer="emergencyContact" type="tel" maxlength="10" inputmode="numeric"
                            oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('emergencyContact')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pincode <span class="text-red-500">*</span></label>
                        <input wire:model.defer="pincode" type="text" maxlength="6" inputmode="numeric"
                            oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                        @error('pincode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                        <select wire:model.live="selectedState" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                        <select wire:model.live="selectedCity" @disabled(empty($cities))
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                            <option value="">Select City</option>
                            @foreach ($cities as $city)
                                @php $cityName = is_array($city) ? ($city['name'] ?? '') : $city; @endphp
                                @if ($cityName !== '')
                                    <option value="{{ $cityName }}">{{ $cityName }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Address <span class="text-red-500">*</span></label>
                    <textarea wire:model.defer="address" rows="3" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    @error('address')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Active toggle --}}
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.defer="teacherActive" class="rounded">
                    <span class="text-sm text-gray-700">Active (can log in)</span>
                </label>
            </div>

            {{-- Fixed footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 flex-shrink-0">
                <button wire:click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md order-3 sm:order-1">Cancel</button>
                <button wire:click="onSave" wire:loading.attr="disabled" wire:target="onSave"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center justify-center gap-1.5 order-1 sm:order-3">
                    <span wire:loading.remove wire:target="onSave">{{ $editId ? 'Update Teacher' : 'Create Teacher' }}</span>
                    <span wire:loading wire:target="onSave">Saving...</span>
                </button>
            </div>
        </div>
    </div>
@endif
