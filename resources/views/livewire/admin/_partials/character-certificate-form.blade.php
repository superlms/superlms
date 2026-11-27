{{-- Issue / edit a character certificate (चरित्र प्रमाण-पत्र) — the school's
     form, field by field: the language it is printed in, the student (their
     name, father's name and date of birth come from the record; a blank there
     is asked for and saved to it), the book and serial numbers, the years
     studied here, the examination with its result and division, whether they
     took part in anything unlawful, the date. In Hindi the names may be typed
     in Hindi too (the record's are printed without them). --}}
@if ($ccModal)
    @php
        $in  = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $lbl = 'block text-sm font-medium text-gray-700 mb-1.5';
        $opt = fn (bool $on) => 'flex items-center gap-2.5 px-3.5 py-2.5 rounded-md border cursor-pointer ' . ($on ? 'border-blue-500 bg-blue-50/60' : 'border-gray-300 hover:bg-gray-50');
        $isHi = $cc_language !== 'en';
    @endphp
    <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden" data-cc-form>
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCcModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col" wire:click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editCcId ? 'Edit' : 'Issue' }} Character Certificate</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Pick the language, the class, then the student, and fill the certificate's details — every field is needed</p>
                </div>
                <button wire:click="closeCcModal" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                {{-- Language --}}
                <div>
                    <label class="{{ $lbl }}">Print in <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="{{ $opt($isHi) }}">
                            <input type="radio" wire:model.live="cc_language" value="hi" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800">Hindi <span class="text-gray-400 font-normal">· हिन्दी</span></span>
                        </label>
                        <label class="{{ $opt(!$isHi) }}">
                            <input type="radio" wire:model.live="cc_language" value="en" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800">English</span>
                        </label>
                    </div>
                    @error('cc_language')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                @include('livewire.partials.certificate-student-picker', [
                    'classProp'    => 'ccClass',
                    'studentProp'  => 'cc_student_id',
                    'sectionProp'  => 'ccSection',
                    'searchProp'   => 'ccStudentSearch',
                    'classValue'   => $ccClass,
                    'sectionsList' => $this->ccSections,
                    'students'     => $this->ccIssueStudents,
                    'standards'    => $this->standards,
                    'selected'     => $this->selectedCcStudent,
                    'selectMethod' => 'selectCcStudent',
                    'clearMethod'  => 'clearCcStudent',
                    'errorKey'     => 'cc_student_id',
                    'locked'       => (bool) $editCcId,
                ])

                @if (count($this->ccMissingFields))
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Missing in the Record</p>
                        <p class="text-xs text-gray-400 mb-3">The certificate prints these from the student's record, where they are blank. Fill them here — they are saved to the record too.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($this->ccMissingFields as $fillKey => $fillLabel)
                                <div wire:key="cc-fill-{{ $fillKey }}">
                                    <label class="{{ $lbl }}">{{ $fillLabel }} <span class="text-red-500">*</span></label>
                                    <input type="{{ $fillKey === 'dob' ? 'date' : 'text' }}" wire:model.defer="ccStudentFill.{{ $fillKey }}"
                                        class="{{ $in }} @error('ccStudentFill.' . $fillKey) border-red-400 @enderror">
                                    @error('ccStudentFill.' . $fillKey)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- The certificate --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Certificate</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $lbl }}">Book No. <span class="text-gray-400 font-normal">(पुस्तक संख्या)</span> <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="cc_book_no" maxlength="50" placeholder="e.g. 10" class="{{ $in }} @error('cc_book_no') border-red-400 @enderror">
                            @error('cc_book_no')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Serial No. <span class="text-gray-400 font-normal">(क्रम संख्या)</span> <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="cc_serial_no" maxlength="50" placeholder="e.g. 23" class="{{ $in }} @error('cc_serial_no') border-red-400 @enderror">
                            @error('cc_serial_no')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="{{ $lbl }}">Student is <span class="text-gray-400 font-normal">(for the wording: श्री / कु०, आत्मज / आत्मजा)</span> <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="{{ $opt($cc_gender !== 'female') }}">
                                    <input type="radio" wire:model.live="cc_gender" value="male" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-800">Male <span class="text-gray-400 font-normal">· छात्र</span></span>
                                </label>
                                <label class="{{ $opt($cc_gender === 'female') }}">
                                    <input type="radio" wire:model.live="cc_gender" value="female" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-800">Female <span class="text-gray-400 font-normal">· छात्रा</span></span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Studied here from (year) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" wire:model.defer="cc_from_year" maxlength="4" placeholder="e.g. 2019" class="{{ $in }} @error('cc_from_year') border-red-400 @enderror">
                            @error('cc_from_year')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">to (year) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" wire:model.defer="cc_to_year" maxlength="4" placeholder="e.g. 2026" class="{{ $in }} @error('cc_to_year') border-red-400 @enderror">
                            @error('cc_to_year')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- The examination --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Examination</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $lbl }}">Examination <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="cc_exam_name" maxlength="100" placeholder="{{ $isHi ? 'e.g. हाईस्कूल / इण्टरमीडिएट' : 'e.g. High School / Intermediate' }}"
                                class="{{ $in }} @error('cc_exam_name') border-red-400 @enderror">
                            @error('cc_exam_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Year of examination <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" wire:model.defer="cc_exam_year" maxlength="4" placeholder="e.g. 2026" class="{{ $in }} @error('cc_exam_year') border-red-400 @enderror">
                            @error('cc_exam_year')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Result <span class="text-red-500">*</span></label>
                            <select wire:model.live="cc_result" class="{{ $in }}">
                                <option value="passed">Passed · उत्तीर्ण</option>
                                <option value="failed">Not passed · अनुत्तीर्ण</option>
                            </select>
                        </div>
                        @if ($cc_result === 'passed')
                            <div>
                                <label class="{{ $lbl }}">Division <span class="text-gray-400 font-normal">(श्रेणी)</span> <span class="text-red-500">*</span></label>
                                <select wire:model.defer="cc_division" class="{{ $in }} @error('cc_division') border-red-400 @enderror">
                                    @foreach (\App\Models\Admin\CharacterCertificate::DIVISIONS as $key => [$dHi, $dEn])
                                        <option value="{{ $key }}">{{ $dEn }} · {{ $dHi }}</option>
                                    @endforeach
                                </select>
                                @error('cc_division')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Conduct and issue --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Conduct &amp; Issue</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="{{ $lbl }}">Took part in any unlawful activity while studying here? <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="{{ $opt($cc_unlawful !== 'yes') }}">
                                    <input type="radio" wire:model.live="cc_unlawful" value="no" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-800">No <span class="text-gray-400 font-normal">· भाग नहीं लिया है</span></span>
                                </label>
                                <label class="{{ $opt($cc_unlawful === 'yes') }}">
                                    <input type="radio" wire:model.live="cc_unlawful" value="yes" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-800">Yes <span class="text-gray-400 font-normal">· भाग लिया था</span></span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Date of issue <span class="text-red-500">*</span></label>
                            <input type="date" wire:model.defer="cc_issue_date" class="{{ $in }} @error('cc_issue_date') border-red-400 @enderror">
                            @error('cc_issue_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Names in Hindi (Hindi certificate only; optional) --}}
                @if ($isHi)
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Names in Hindi <span class="normal-case tracking-normal font-normal">(optional)</span></p>
                        <p class="text-xs text-gray-400 mb-3">Typed here, they are printed on the Hindi certificate; left blank, the names as in the record are printed. The school's name and place are kept for the next certificate.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $lbl }}">Student's name (Hindi)</label>
                                <input type="text" wire:model.defer="cc_student_name_hi" maxlength="255" placeholder="e.g. राहुल कुमार" class="{{ $in }}">
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Father's name (Hindi)</label>
                                <input type="text" wire:model.defer="cc_father_name_hi" maxlength="255" placeholder="e.g. सुरेश कुमार" class="{{ $in }}">
                            </div>
                            <div>
                                <label class="{{ $lbl }}">School's name (Hindi)</label>
                                <input type="text" wire:model.defer="cc_school_name_hi" maxlength="255" placeholder="e.g. आदर्श सत्यदेव इण्टर कालिज" class="{{ $in }}">
                            </div>
                            <div>
                                <label class="{{ $lbl }}">Place (Hindi)</label>
                                <input type="text" wire:model.defer="cc_school_place_hi" maxlength="255" placeholder="e.g. मालव (अलीगढ़)" class="{{ $in }}">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" wire:click="closeCcModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="button" wire:click="saveCc" wire:loading.attr="disabled" wire:target="saveCc"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveCc">{{ $editCcId ? 'Update Certificate' : 'Issue Certificate' }}</span>
                    <span wire:loading wire:target="saveCc">Saving...</span>
                </button>
            </div>
        </div>
    </div>
@endif
