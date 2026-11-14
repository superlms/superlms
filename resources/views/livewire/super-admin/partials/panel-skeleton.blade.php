{{-- A slide-in panel of the Super Admin as a skeleton (Schools: New / Edit
     School, Bank Details, Online Payment; Fees: Fee Structure, Update Fee, a
     payment, Record / Mark Paid), shown where the panel opens once it has
     taken longer than ListSkeleton::DELAY_MS: the header (its title, or a
     bar), the fields in the login look (a rounded box with its name on the
     top edge) and the footer's buttons.

     $title (optional); $width (default max-w-3xl); $fields (default 8);
     $cols (1 or 2, default 2); $z (default z-50). --}}
@php
    $fields = $fields ?? 8;
    $vary   = [42, 30, 50, 36, 46, 28, 40, 34, 48, 32, 44, 38];
@endphp
<div class="fixed inset-x-0 bottom-0 top-16 {{ $z ?? 'z-50' }} overflow-hidden" aria-busy="true" data-panel-skeleton>
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]"></div>
    <div class="absolute top-0 right-0 bottom-0 z-10 w-full {{ $width ?? 'max-w-3xl' }} bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="min-w-0 flex-1 space-y-1.5">
                @if (!empty($title))
                    <p class="text-lg font-semibold text-gray-900 truncate">{{ $title }}</p>
                @else
                    <span class="sa-skel block h-5 w-40"></span>
                @endif
                <span class="sa-skel block h-2.5 w-56 max-w-full"></span>
            </div>
            <span class="sa-skel w-8 h-8 rounded-md flex-shrink-0"></span>
        </div>

        <div class="flex-1 overflow-hidden px-6 py-7">
            <div class="grid grid-cols-1 {{ ($cols ?? 2) > 1 ? 'sm:grid-cols-2' : '' }} gap-x-5 gap-y-7">
                @for ($i = 0; $i < $fields; $i++)
                    <div class="relative h-11 rounded-full border-[1.5px] border-gray-200">
                        <span class="sa-skel absolute -top-1.5 left-4 h-3 ring-4 ring-white" style="width: {{ $vary[$i % count($vary)] }}%"></span>
                        <span class="sa-skel absolute top-1/2 -translate-y-1/2 left-5 h-2.5 opacity-60" style="width: {{ $vary[($i + 5) % count($vary)] + 10 }}%"></span>
                    </div>
                @endfor
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0">
            <span class="sa-skel h-9 w-20 rounded-lg"></span>
            <span class="sa-skel h-9 w-32 rounded-lg"></span>
        </div>
    </div>
</div>
