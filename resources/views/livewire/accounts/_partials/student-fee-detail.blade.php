{{-- Student Fee Detail Partial --}}
{{-- Expects $data array from buildStudentFeeData() --}}
<div class="space-y-6">

    {{-- Student Info Card --}}
    <div class="bg-emerald-50/70 rounded-xl p-5 border border-emerald-100">
        <div class="flex flex-col sm:flex-row gap-5">
            {{-- Profile Picture --}}
            <div class="flex-shrink-0">
                @if(!empty($data['student']['image']))
                    <img src="{{ $data['student']['image'] }}"
                         alt="{{ $data['student']['full_name'] }}"
                         class="w-20 h-20 rounded-full object-cover border-2 border-emerald-200 shadow-sm">
                @else
                    <div class="w-20 h-20 rounded-full bg-emerald-200 flex items-center justify-center border-2 border-emerald-300 shadow-sm">
                        <span class="text-2xl font-bold text-emerald-700">
                            {{ strtoupper(substr($data['student']['full_name'], 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Student Details Grid --}}
            <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-3 text-sm">
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Name</p>
                    <p class="font-semibold text-emerald-800">{{ $data['student']['full_name'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Class - Section</p>
                    <p class="font-semibold text-gray-800">{{ $data['student']['class_section'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Admission No</p>
                    <p class="font-semibold text-gray-800">{{ $data['student']['admission_no'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Phone</p>
                    <p class="font-semibold text-gray-800">{{ $data['student']['phone'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Email</p>
                    <p class="font-semibold text-gray-800">{{ $data['student']['email'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Father's Name</p>
                    <p class="font-semibold text-gray-800">{{ $data['student']['father_name'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Mother's Name</p>
                    <p class="font-semibold text-gray-800">{{ $data['student']['mother_name'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">Total Collected / Remaining</p>
                    <p class="font-semibold">
                        <span class="text-emerald-700">{{ number_format($data['totalPaid'], 2) }}</span>
                        <span class="text-gray-400 mx-0.5">/</span>
                        <span class="text-red-600">{{ number_format($data['remaining'], 2) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Fee Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Total Fee</p>
            <p class="text-base font-bold text-emerald-700 mt-1">{{ number_format($data['totalFee'], 2) }}</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Academic Fee</p>
            <p class="text-base font-bold text-emerald-600 mt-1">{{ number_format($data['academicTotal'], 2) }}</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Transport Fee</p>
            <p class="text-base font-bold text-blue-600 mt-1">
                {{ $data['hasTransport'] ? number_format($data['transportTotal'], 2) : 'N/A' }}
            </p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Academic Paid</p>
            <p class="text-base font-bold text-emerald-600 mt-1">{{ number_format($data['academicPaid'], 2) }}</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Transport Paid</p>
            <p class="text-base font-bold text-blue-600 mt-1">{{ number_format($data['transportPaid'], 2) }}</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Remaining</p>
            <p class="text-base font-bold text-red-600 mt-1">{{ number_format($data['remaining'], 2) }}</p>
        </div>
        <div class="bg-white border border-emerald-100 rounded-xl p-3">
            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Penalties</p>
            <p class="text-base font-bold text-orange-600 mt-1">{{ number_format($data['penalties'], 2) }}</p>
        </div>
    </div>

    {{-- Academic Fee Structure --}}
    <div>
        <h3 class="text-sm font-semibold text-emerald-700 mb-2 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Academic Fee Structure
        </h3>
        <div class="overflow-x-auto rounded-lg border border-emerald-100">
            <table class="w-full text-sm">
                <thead class="bg-emerald-50">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">#</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Fee Name</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-emerald-700">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-50">
                    @forelse($data['academicStructures'] as $index => $s)
                        <tr class="hover:bg-emerald-50/50 transition">
                            <td class="px-3 py-2 text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 text-gray-800">{{ $s['fee_name'] }}</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-800">{{ number_format($s['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-4 text-center text-gray-400">No academic fee structure found.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($data['academicStructures']) > 0)
                    <tfoot class="bg-emerald-50/50">
                        <tr>
                            <td colspan="2" class="px-3 py-2.5 text-right font-semibold text-emerald-700">Total Academic Fee</td>
                            <td class="px-3 py-2.5 text-right font-bold text-emerald-700">{{ number_format($data['academicTotal'], 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Transport Fee Structure --}}
    @if($data['hasTransport'])
        <div>
            <h3 class="text-sm font-semibold text-blue-700 mb-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Transport Fee Structure
            </h3>
            <div class="overflow-x-auto rounded-lg border border-blue-100">
                <table class="w-full text-sm">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold text-blue-700">#</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-blue-700">Fee Name</th>
                            <th class="px-3 py-2.5 text-right font-semibold text-blue-700">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50">
                        @forelse($data['transportStructures'] as $index => $s)
                            <tr class="hover:bg-blue-50/50 transition">
                                <td class="px-3 py-2 text-gray-600">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 text-gray-800">{{ $s['fee_name'] }}</td>
                                <td class="px-3 py-2 text-right font-medium text-gray-800">{{ number_format($s['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-gray-400">No transport fee structure found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($data['transportStructures']) > 0)
                        <tfoot class="bg-blue-50/50">
                            <tr>
                                <td colspan="2" class="px-3 py-2.5 text-right font-semibold text-blue-700">Total Transport Fee</td>
                                <td class="px-3 py-2.5 text-right font-bold text-blue-700">{{ number_format($data['transportTotal'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- All Payments List --}}
    <div>
        <h3 class="text-sm font-semibold text-emerald-700 mb-2 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Payment History
        </h3>
        <div class="overflow-x-auto rounded-lg border border-emerald-100">
            <table class="w-full text-sm">
                <thead class="bg-emerald-50">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">#</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Name</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Class - Section</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Adm No</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Fee Type</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Mode</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-emerald-700">Amount</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Receipt #</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Date</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-emerald-700">Collected By</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-emerald-700">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-50">
                    @forelse($data['payments'] as $index => $p)
                        <tr class="hover:bg-emerald-50/50 transition">
                            <td class="px-3 py-2.5 text-gray-600">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5 font-medium text-gray-800">{{ $p['student_name'] }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $p['class_section'] }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $p['admission_no'] }}</td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $p['fee_type'] === 'academic' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst($p['fee_type']) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-gray-600">{{ ucfirst(str_replace('_', ' ', $p['payment_mode'])) }}</td>
                            <td class="px-3 py-2.5 text-right font-medium text-gray-800">{{ number_format($p['amount'], 2) }}</td>
                            <td class="px-3 py-2.5 font-mono text-xs text-gray-600">{{ $p['receipt_number'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $p['payment_date'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ $p['collected_by'] }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <a href="{{ route('admin.fee.receipt', ['organization' => auth()->user()->organization_id, 'id' => $p['id']]) }}" target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-medium hover:bg-emerald-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-6 text-center text-gray-400">No payments found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
