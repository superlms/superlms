<div class="min-h-screen bg-gray-50">
    <style>[x-cloak]{display:none !important;}</style>
    @php
        $inr        = fn ($v) => '₹' . number_format((float) $v, 2);
        $inrShort   = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
        $qty        = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $filterIcon = 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z';
        $in         = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $inSm       = 'w-full px-2.5 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $lb         = 'block text-sm font-medium text-gray-700 mb-1.5';
        $filterSel  = 'text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700';
        $statusCls  = [
            'draft'     => 'bg-gray-100 text-gray-700',
            'sent'      => 'bg-blue-50 text-blue-700',
            'paid'      => 'bg-emerald-50 text-emerald-700',
            'cancelled' => 'bg-red-50 text-red-600',
        ];
        $statuses = \App\Models\Store\StoreInvoice::STATUSES;
    @endphp

    {{-- ══════════ STICKY: HEADER + TABS + FILTERS ══════════ --}}
    <div class="sticky top-0 z-40">
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 sm:min-h-9">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Store</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Products: <strong class="text-gray-800">{{ number_format($counts['active']) }}</strong>{{ $counts['products'] > $counts['active'] ? ' of ' . number_format($counts['products']) : '' }}</span>
                        <span class="px-4">Billed: <strong class="text-gray-800">{{ $inr($counts['billed']) }}</strong></span>
                        <span class="px-4">Received: <strong class="text-emerald-600">{{ $inr($counts['paid']) }}</strong></span>
                        <span class="pl-4">To receive: <strong class="{{ $counts['due'] > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $inr($counts['due']) }}</strong></span>
                    </div>
                    @if ($ready)
                        @if ($tab === 'store')
                            <button wire:click="openProduct"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Product
                            </button>
                        @else
                            <button wire:click="openSettings"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Settings
                            </button>
                            <button wire:click="openInvoice"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                New Invoice
                            </button>
                        @endif
                    @endif
                </div>
            </div>
            <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Products: <strong class="text-gray-800">{{ number_format($counts['active']) }}</strong></span>
                <span>Billed: <strong class="text-gray-800">{{ $inr($counts['billed']) }}</strong></span>
                <span>Received: <strong class="text-emerald-600">{{ $inr($counts['paid']) }}</strong></span>
                <span>To receive: <strong class="text-amber-600">{{ $inr($counts['due']) }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
            <nav class="flex gap-1 overflow-x-auto">
                @foreach (\App\Livewire\SuperAdmin\Store::TABS as $key => $label)
                    <button wire:click="setTab('{{ $key }}')"
                        class="py-3 px-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors inline-flex items-center gap-1.5
                            {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                        <span class="min-w-[1.25rem] h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[11px] font-semibold {{ $tab === $key ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">{{ $key === 'store' ? $counts['products'] : $counts['invoices'] }}</span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Filters --}}
        @if ($ready)
            <div class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $filterIcon }}" /></svg>
                        Filter by:
                    </div>
                    @if ($tab === 'store')
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search title, SKU, category…"
                            class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 max-w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <select wire:model.live="category" aria-label="Category" class="{{ $filterSel }}">
                            <option value="">Every category</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="shown" aria-label="Shown" class="{{ $filterSel }}">
                            <option value="">Shown and hidden</option>
                            <option value="active">Shown</option>
                            <option value="hidden">Hidden</option>
                        </select>
                    @else
                        <input wire:model.live.debounce.300ms="invSearch" type="text" placeholder="Search number or school…"
                            class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 max-w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <select wire:model.live="invSchool" aria-label="School" class="{{ $filterSel }} min-w-[12rem] max-w-full">
                            <option value="">Every school</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="invStatus" aria-label="Status" class="{{ $filterSel }}">
                            <option value="">Every status</option>
                            @foreach ($statuses as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                            <option value="overdue">Overdue</option>
                        </select>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="p-4 sm:p-6 space-y-4">
        @unless ($ready)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
                The Store is not set up yet — its tables come with the next migration.
            </div>
        @endunless

        {{-- ════════ STORE ════════ --}}
        @if ($ready && $tab === 'store')
            @if ($products->isEmpty())
                <div class="bg-white border border-gray-200 rounded-xl px-6 py-14 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center">
                        <x-icon name="shopping-bag" class="w-6 h-6 text-blue-600" />
                    </div>
                    @if ($counts['products'] === 0)
                        <h3 class="mt-3 text-sm font-semibold text-gray-900">No products yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Add what you sell to schools — with its pictures, price and specifications.</p>
                        <button wire:click="openProduct" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg">Add Product</button>
                    @else
                        <h3 class="mt-3 text-sm font-semibold text-gray-900">No product matches</h3>
                        <p class="mt-1 text-sm text-gray-500">Try another search or filter.</p>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($products as $p)
                        <div wire:key="product-{{ $p->id }}" class="bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col {{ $p->is_active ? '' : 'opacity-70' }}">
                            <button type="button" wire:click="openProduct({{ $p->id }})" class="relative block aspect-[4/3] bg-gray-100 overflow-hidden group">
                                @if ($p->coverUrl())
                                    <img src="{{ $p->coverUrl() }}" alt="{{ $p->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300">
                                @else
                                    <span class="absolute inset-0 flex items-center justify-center text-gray-300">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5A1.5 1.5 0 0020.25 3H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21zm9-12.75h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                                    </span>
                                @endif
                                @if (!$p->is_active)
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-gray-900/80 text-white text-[11px] font-medium">Hidden</span>
                                @endif
                                @if (count((array) $p->images) > 1)
                                    <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-full bg-black/60 text-white text-[11px] font-medium">{{ count((array) $p->images) }} photos</span>
                                @endif
                            </button>
                            <div class="p-3.5 flex-1 flex flex-col">
                                @if ($p->category)
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 truncate">{{ $p->category }}</p>
                                @endif
                                <button type="button" wire:click="openProduct({{ $p->id }})" class="text-left text-sm font-semibold text-gray-900 hover:text-blue-700 line-clamp-2">{{ $p->title }}</button>
                                <div class="mt-1.5 flex items-baseline flex-wrap gap-x-2">
                                    <span class="text-base font-bold text-gray-900">{{ $inrShort($p->price) }}</span>
                                    @if ($p->mrp && (float) $p->mrp > (float) $p->price)
                                        <span class="text-xs text-gray-400 line-through">{{ $inrShort($p->mrp) }}</span>
                                        <span class="text-xs font-semibold text-emerald-600">{{ (int) round((1 - (float) $p->price / (float) $p->mrp) * 100) }}% off</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    {{ (float) $p->gst_rate > 0 ? '+ ' . $qty($p->gst_rate) . '% GST' : 'No GST' }}{{ $p->unit ? ' · per ' . $p->unit : '' }}{{ $p->stock !== null ? ' · ' . number_format($p->stock) . ' in stock' : '' }}
                                </p>
                                @if (!empty($p->specifications))
                                    <ul class="mt-2 space-y-0.5">
                                        @foreach (array_slice($p->specifications, 0, 2) as $spec)
                                            <li class="text-xs text-gray-600 truncate"><span class="text-gray-400">{{ $spec['label'] ?? '' }}:</span> {{ $spec['value'] ?? '' }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="mt-auto pt-3 flex items-center gap-1 border-t border-gray-100 -mx-3.5 px-3.5 mt-3">
                                    <button wire:click="openProduct({{ $p->id }})" class="px-2.5 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-md">Edit</button>
                                    <button wire:click="toggleActive({{ $p->id }})" class="px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md">{{ $p->is_active ? 'Hide' : 'Show' }}</button>
                                    <button wire:click="confirmDeleteProduct({{ $p->id }})" class="ml-auto px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div>{{ $products->links() }}</div>
            @endif
        @endif

        {{-- ════════ INVOICES ════════ --}}
        @if ($ready && $tab === 'invoices')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ([
                    ['Billed', $inr($counts['billed']), number_format($counts['invoices']) . ' invoice' . ($counts['invoices'] === 1 ? '' : 's') . ', cancelled left out', 'text-gray-900'],
                    ['Received', $inr($counts['paid']), 'invoices marked paid', 'text-emerald-600'],
                    ['To receive', $inr($counts['due']), $counts['overdue'] ? $counts['overdue'] . ' past the due day' : 'nothing overdue', $counts['due'] > 0 ? 'text-amber-600' : 'text-gray-900'],
                    ['Drafts', number_format($counts['drafts']), 'made, not sent yet', 'text-gray-900'],
                ] as [$tLabel, $tValue, $tSub, $tCls])
                    <div class="bg-white border border-gray-200 rounded-xl px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $tLabel }}</p>
                        <p class="text-lg font-bold mt-0.5 {{ $tCls }}">{{ $tValue }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $tSub }}</p>
                    </div>
                @endforeach
            </div>

            @if ($invoices->isEmpty())
                <div class="bg-white border border-gray-200 rounded-xl px-6 py-14 text-center">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $counts['invoices'] === 0 && $invSearch === '' && $invSchool === '' && $invStatus === '' ? 'No invoices yet' : 'No invoice matches' }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Bill a school for what it buys — its lines from the Store, with an agreement when you need one.</p>
                    <button wire:click="openInvoice" class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg">New Invoice</button>
                </div>
            @else
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold">Invoice</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">School</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Amount</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Due</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Status</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Agreement</th>
                                    <th class="px-4 py-2.5 text-right font-semibold"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($invoices as $inv)
                                    <tr wire:key="inv-{{ $inv->id }}" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <button wire:click="openView({{ $inv->id }})" class="font-semibold text-gray-900 hover:text-blue-700">{{ $inv->number }}</button>
                                            <p class="text-xs text-gray-500">{{ $inv->invoice_date?->format('d M Y') }} · {{ $inv->items_count }} item{{ $inv->items_count === 1 ? '' : 's' }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="text-gray-800 truncate max-w-[16rem]">{{ $inv->organization?->name ?? $inv->schoolName() }}</p>
                                            @if ($inv->sent_at)
                                                <p class="text-xs text-gray-500">Emailed {{ $inv->sent_at->format('d M, g:i A') }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $inr($inv->total) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($inv->status === 'paid')
                                                <span class="text-xs text-gray-500">Paid {{ $inv->paid_at?->format('d M Y') }}</span>
                                            @elseif ($inv->due_date)
                                                <span class="text-xs {{ $inv->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ $inv->due_date->format('d M Y') }}{{ $inv->isOverdue() ? ' · overdue' : '' }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls[$inv->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $statuses[$inv->status] ?? $inv->status }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if (!$inv->hasAgreement())
                                                <span class="text-xs text-gray-400">—</span>
                                            @elseif ($inv->agreement_signed_at)
                                                <span class="text-xs font-medium text-emerald-700">Signed</span>
                                            @else
                                                <span class="text-xs text-gray-600">Not signed</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <button wire:click="openView({{ $inv->id }})" class="px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-md">Open</button>
                                            <button wire:click="downloadPdf({{ $inv->id }})" class="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md">PDF</button>
                                            @if ($inv->status !== 'cancelled')
                                                <button wire:click="openSend({{ $inv->id }})" class="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-md">{{ $inv->sent_at ? 'Resend' : 'Send' }}</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>{{ $invoices->links() }}</div>
            @endif
        @endif
    </div>

    {{-- ══════════ PRODUCT SLIDE-IN ══════════ --}}
    @if ($showProduct)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeProduct"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $productId ? 'Edit Product' : 'Add Product' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">What schools can buy from SuperLMS</p>
                    </div>
                    <button wire:click="closeProduct" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                    {{-- Images --}}
                    <section>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Images</label>
                            <span class="text-xs text-gray-400">{{ count($pImages) + count($pUploads) }} of {{ \App\Models\Store\StoreProduct::MAX_IMAGES }} · the first is the cover</span>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                            @foreach ($pImages as $i => $url)
                                <div wire:key="pimg-{{ md5($url) }}" class="relative aspect-square rounded-lg overflow-hidden border {{ $i === 0 ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200' }} bg-gray-50 group">
                                    <img src="{{ $url }}" alt="" class="w-full h-full object-cover">
                                    @if ($i === 0)
                                        <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-blue-600 text-white text-[10px] font-semibold">Cover</span>
                                    @endif
                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 p-1 bg-gradient-to-t from-black/60 to-transparent">
                                        <div class="flex gap-0.5">
                                            <button type="button" wire:click="moveImage({{ $i }}, -1)" @disabled($i === 0) title="Move left" class="w-6 h-6 flex items-center justify-center rounded bg-white/90 text-gray-700 disabled:opacity-40">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                                            </button>
                                            <button type="button" wire:click="moveImage({{ $i }}, 1)" @disabled($i === count($pImages) - 1) title="Move right" class="w-6 h-6 flex items-center justify-center rounded bg-white/90 text-gray-700 disabled:opacity-40">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                            </button>
                                        </div>
                                        <div class="flex gap-0.5">
                                            @if ($i > 0)
                                                <button type="button" wire:click="makeCover({{ $i }})" title="Make cover" class="h-6 px-1.5 rounded bg-white/90 text-gray-700 text-[10px] font-semibold">Cover</button>
                                            @endif
                                            <button type="button" wire:click="removeImage({{ $i }})" title="Remove" class="w-6 h-6 flex items-center justify-center rounded bg-white/90 text-red-600">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @foreach ($pUploads as $i => $upload)
                                <div wire:key="pup-{{ $i }}-{{ $upload->getFilename() }}" class="relative aspect-square rounded-lg overflow-hidden border border-dashed border-blue-300 bg-blue-50">
                                    @php
                                        try { $preview = $upload->temporaryUrl(); } catch (\Throwable $e) { $preview = null; }
                                    @endphp
                                    @if ($preview)
                                        <img src="{{ $preview }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <span class="absolute inset-0 flex items-center justify-center text-xs text-blue-700 px-2 text-center">{{ $upload->getClientOriginalName() }}</span>
                                    @endif
                                    @if (count($pImages) === 0 && $i === 0)
                                        <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-blue-600 text-white text-[10px] font-semibold">Cover</span>
                                    @endif
                                    <span class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 rounded bg-white/90 text-blue-700 text-[10px] font-semibold">New</span>
                                    <button type="button" wire:click="removeUpload({{ $i }})" title="Remove" class="absolute bottom-1 right-1 w-6 h-6 flex items-center justify-center rounded bg-white/90 text-red-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            @endforeach
                            @if (count($pImages) + count($pUploads) < \App\Models\Store\StoreProduct::MAX_IMAGES)
                                {{-- Several picked at once go up one after another: Livewire's S3
                                     temporary uploads take one file at a time. --}}
                                <label x-data="{ left: 0 }" class="relative aspect-square rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-400 hover:bg-blue-50/40 flex flex-col items-center justify-center gap-1 cursor-pointer text-gray-500 transition-colors">
                                    <input type="file" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" x-bind:disabled="left > 0"
                                        x-on:change="const files = [...$event.target.files]; $event.target.value = '';
                                            left = files.length;
                                            for (const file of files) {
                                                await new Promise((done) => $wire.upload('pOne', file, done, done));
                                                left--;
                                            }">
                                    <span x-show="! left" class="flex flex-col items-center gap-1">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                        <span class="text-xs font-medium">Add images</span>
                                    </span>
                                    <span x-show="left" x-cloak class="text-xs font-medium text-blue-600 text-center px-1" x-text="'Uploading… ' + left + ' left'"></span>
                                </label>
                            @endif
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">JPG, PNG or WEBP, up to 5 MB each. Pick several at once.</p>
                        @error('pUploads')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        @error('pOne')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        @error('pUploads.*')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </section>

                    {{-- Details --}}
                    <section class="space-y-4">
                        <div>
                            <label class="{{ $lb }}">Title <span class="text-red-500">*</span></label>
                            <input wire:model="pTitle" type="text" placeholder="e.g. Biometric attendance machine" class="{{ $in }}">
                            @error('pTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="{{ $lb }}">Category</label>
                                <input wire:model="pCategory" type="text" list="store-categories" placeholder="e.g. Hardware" class="{{ $in }}">
                                <datalist id="store-categories">
                                    @foreach ($categories as $c)
                                        <option value="{{ $c }}"></option>
                                    @endforeach
                                </datalist>
                                @error('pCategory')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $lb }}">SKU / code</label>
                                <input wire:model="pSku" type="text" placeholder="Optional" class="{{ $in }}">
                                @error('pSku')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $lb }}">Unit</label>
                                <input wire:model="pUnit" type="text" list="store-units" placeholder="piece, set, box…" class="{{ $in }}">
                                <datalist id="store-units">
                                    @foreach (['piece', 'set', 'box', 'pack', 'kit', 'licence', 'year', 'month'] as $u)
                                        <option value="{{ $u }}"></option>
                                    @endforeach
                                </datalist>
                                @error('pUnit')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="{{ $lb }}">Price (₹) <span class="text-red-500">*</span></label>
                                <input wire:model="pPrice" type="number" step="0.01" min="0" placeholder="0.00" class="{{ $in }}">
                                @error('pPrice')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $lb }}">MRP (₹)</label>
                                <input wire:model="pMrp" type="number" step="0.01" min="0" placeholder="Optional" class="{{ $in }}">
                                @error('pMrp')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $lb }}">GST %</label>
                                <input wire:model="pGst" type="number" step="0.01" min="0" max="100" list="store-gst" class="{{ $in }}">
                                <datalist id="store-gst">
                                    @foreach ([0, 5, 12, 18, 28] as $g)
                                        <option value="{{ $g }}"></option>
                                    @endforeach
                                </datalist>
                                @error('pGst')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $lb }}">Stock</label>
                                <input wire:model="pStock" type="number" step="1" min="0" placeholder="Optional" class="{{ $in }}">
                                @error('pStock')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="{{ $lb }}">Description</label>
                            <textarea wire:model="pDescription" rows="5" placeholder="What it is, what it does, what comes in the box…" class="{{ $in }}"></textarea>
                            @error('pDescription')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    {{-- Specifications --}}
                    <section>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Specifications</label>
                            <button type="button" wire:click="addSpec" class="text-xs font-medium text-blue-600 hover:text-blue-800">+ Add specification</button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($pSpecs as $i => $spec)
                                <div wire:key="spec-{{ $i }}" class="flex items-start gap-2">
                                    <div class="w-2/5">
                                        <input wire:model="pSpecs.{{ $i }}.label" type="text" placeholder="e.g. Warranty" class="{{ $inSm }}">
                                        @error('pSpecs.' . $i . '.label')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="flex-1">
                                        <input wire:model="pSpecs.{{ $i }}.value" type="text" placeholder="e.g. 1 year" class="{{ $inSm }}">
                                        @error('pSpecs.' . $i . '.value')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                    <button type="button" wire:click="removeSpec({{ $i }})" title="Remove" class="mt-1 w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">Leave a row empty to drop it.</p>
                    </section>

                    <label class="flex items-start gap-3 cursor-pointer border border-gray-200 rounded-md px-3.5 py-3">
                        <input type="checkbox" wire:model="pActive" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Shown in the Store</span>
                            <span class="block text-xs text-gray-500 mt-0.5">Hidden products stay on invoices already made but can't be added to new ones.</span>
                        </span>
                    </label>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center gap-2 flex-shrink-0">
                    @if ($productId)
                        <button wire:click="confirmDeleteProduct({{ $productId }})" class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                    @endif
                    <button wire:click="closeProduct" class="ml-auto px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveProduct" wire:loading.attr="disabled" wire:target="saveProduct,pOne"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveProduct">{{ $productId ? 'Save Changes' : 'Add Product' }}</span>
                        <span wire:loading wire:target="saveProduct">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ INVOICE SLIDE-IN (make / edit) ══════════ --}}
    @if ($showInvoice && $formSums)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeInvoice"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-4xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $invoiceId ? 'Edit Invoice' : 'New Invoice' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $invoiceId ? 'Changes show on the PDF the next time it is downloaded or sent' : 'It gets its number when saved' }}</p>
                    </div>
                    <button wire:click="closeInvoice" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-1">
                            <label class="{{ $lb }}">School <span class="text-red-500">*</span></label>
                            <select wire:model="iOrgId" class="{{ $in }}">
                                <option value="">Pick a school…</option>
                                @foreach ($schools as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('iOrgId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lb }}">Invoice date <span class="text-red-500">*</span></label>
                            <input wire:model="iDate" type="date" class="{{ $in }}">
                            @error('iDate')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lb }}">Due by</label>
                            <input wire:model="iDue" type="date" class="{{ $in }}">
                            @error('iDue')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Lines --}}
                    <section>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <label class="text-sm font-medium text-gray-700">Items</label>
                            <div class="flex items-center gap-2">
                                <select wire:model.live="iPick" aria-label="Add from the Store" class="text-sm border border-gray-300 rounded-md pl-3 pr-8 py-1.5 text-gray-700 max-w-xs">
                                    <option value="">+ Add from the Store…</option>
                                    @foreach ($pickable as $pp)
                                        <option value="{{ $pp->id }}">{{ $pp->title }} — {{ $inrShort($pp->price) }}{{ $pp->unit ? ' / ' . $pp->unit : '' }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="addLine" class="px-3 py-1.5 text-sm font-medium text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md">+ Line</button>
                            </div>
                        </div>
                        <div class="border border-gray-200 rounded-lg overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Item</th>
                                        <th class="px-2 py-2 text-left font-semibold w-20">Qty</th>
                                        <th class="px-2 py-2 text-left font-semibold w-24">Unit</th>
                                        <th class="px-2 py-2 text-left font-semibold w-28">Rate (₹)</th>
                                        <th class="px-2 py-2 text-left font-semibold w-20">GST %</th>
                                        <th class="px-3 py-2 text-right font-semibold w-28">Amount</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($iItems as $i => $line)
                                        <tr wire:key="line-{{ $i }}" class="align-top">
                                            <td class="px-3 py-2 min-w-[14rem]">
                                                <input wire:model="iItems.{{ $i }}.title" type="text" placeholder="Item" class="{{ $inSm }}">
                                                @error('iItems.' . $i . '.title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                                <input wire:model="iItems.{{ $i }}.description" type="text" placeholder="Detail" class="mt-1.5 w-full px-2.5 py-1.5 border border-gray-200 rounded-md text-xs text-gray-600 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                                @if (!empty($line['product_id']))
                                                    <p class="mt-1 text-[11px] text-blue-600">From the Store</p>
                                                @endif
                                            </td>
                                            <td class="px-2 py-2">
                                                <input wire:model.live.debounce.400ms="iItems.{{ $i }}.quantity" type="number" step="0.01" min="0" class="{{ $inSm }}">
                                                @error('iItems.' . $i . '.quantity')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                            </td>
                                            <td class="px-2 py-2">
                                                <input wire:model="iItems.{{ $i }}.unit" type="text" list="store-units-inv" placeholder="piece" class="{{ $inSm }}">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input wire:model.live.debounce.400ms="iItems.{{ $i }}.unit_price" type="number" step="0.01" min="0" class="{{ $inSm }}">
                                                @error('iItems.' . $i . '.unit_price')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                            </td>
                                            <td class="px-2 py-2">
                                                <input wire:model.live.debounce.400ms="iItems.{{ $i }}.gst_rate" type="number" step="0.01" min="0" max="100" list="store-gst-inv" class="{{ $inSm }}">
                                                @error('iItems.' . $i . '.gst_rate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium text-gray-900 whitespace-nowrap pt-4">{{ $inr($formSums['lines'][$i]['amount'] ?? 0) }}</td>
                                            <td class="px-1 py-2">
                                                <button type="button" wire:click="removeLine({{ $i }})" title="Remove" class="mt-0.5 w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <datalist id="store-units-inv">
                                @foreach (['piece', 'set', 'box', 'pack', 'kit', 'licence', 'year', 'month'] as $u)
                                    <option value="{{ $u }}"></option>
                                @endforeach
                            </datalist>
                            <datalist id="store-gst-inv">
                                @foreach ([0, 5, 12, 18, 28] as $g)
                                    <option value="{{ $g }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        @error('iItems')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror

                        {{-- Totals --}}
                        <div class="mt-4 flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <label class="{{ $lb }}">Notes</label>
                                <textarea wire:model="iNotes" rows="4" placeholder="Shown on the invoice — delivery details, a reference…" class="{{ $in }}"></textarea>
                                @error('iNotes')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:w-72 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm space-y-2 self-start">
                                <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span class="font-medium">{{ $inr($formSums['subtotal']) }}</span></div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-gray-600">Discount (₹)</span>
                                    <input wire:model.live.debounce.400ms="iDiscount" type="number" step="0.01" min="0" placeholder="0" class="w-28 px-2 py-1 border border-gray-300 rounded-md text-sm text-right focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                @error('iDiscount')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                                @if ($formSums['discount'] > 0)
                                    <div class="flex justify-between"><span class="text-gray-600">Taxable amount</span><span class="font-medium">{{ $inr($formSums['taxable']) }}</span></div>
                                @endif
                                @foreach ($formSums['by_rate'] as $rate => $tax)
                                    <div class="flex justify-between"><span class="text-gray-600">GST @ {{ $rate }}%</span><span class="font-medium">{{ $inr($tax) }}</span></div>
                                @endforeach
                                <div class="flex justify-between border-t border-gray-300 pt-2 text-base"><span class="font-semibold text-gray-900">Total</span><span class="font-bold text-gray-900">{{ $inr($formSums['total']) }}</span></div>
                            </div>
                        </div>
                    </section>

                    {{-- Agreement --}}
                    <section class="border border-gray-200 rounded-lg">
                        <label class="flex items-start gap-3 cursor-pointer px-4 py-3">
                            <input type="checkbox" wire:model.live="iAgreement" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-800">Send an agreement with this invoice</span>
                                <span class="block text-xs text-gray-500 mt-0.5">It goes on its own page of the PDF, with a place for both sides to sign. You can attach a document of your own too.</span>
                            </span>
                        </label>
                        @if ($iAgreement)
                            <div class="border-t border-gray-200 px-4 py-4 space-y-4">
                                <div>
                                    <label class="{{ $lb }}">Title</label>
                                    <input wire:model="iAgreementTitle" type="text" placeholder="Purchase Agreement" class="{{ $in }}">
                                    @error('iAgreementTitle')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $lb }}">Agreement</label>
                                    <textarea wire:model="iAgreementBody" rows="12" class="{{ $in }} font-mono text-[13px] leading-relaxed"></textarea>
                                    <p class="mt-1 text-xs text-gray-400">These fill themselves in: {{ implode(' ', \App\Services\StoreInvoices::PLACEHOLDERS) }}. The default text is set in Settings.</p>
                                    @error('iAgreementBody')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="{{ $lb }}">Agreement document</label>
                                    @if ($iAgreementFile)
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="px-2.5 py-1.5 bg-blue-50 text-blue-700 rounded-md truncate max-w-xs">{{ $iAgreementFile->getClientOriginalName() }}</span>
                                            <button type="button" wire:click="removeAgreementFile" class="text-xs font-medium text-red-600 hover:text-red-800">Remove</button>
                                        </div>
                                    @elseif ($iAgreementFileUrl)
                                        <div class="flex items-center gap-2 text-sm">
                                            <a href="{{ $iAgreementFileUrl }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 bg-gray-100 text-gray-700 rounded-md truncate max-w-xs hover:bg-gray-200">{{ $iAgreementFileName ?: 'Agreement document' }}</a>
                                            <button type="button" wire:click="removeAgreementFile" class="text-xs font-medium text-red-600 hover:text-red-800">Remove</button>
                                        </div>
                                    @else
                                        <input type="file" wire:model="iAgreementFile" accept=".pdf,.doc,.docx,image/jpeg,image/png"
                                            class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700 file:text-sm file:font-medium hover:file:bg-gray-200">
                                        <p class="mt-1 text-xs text-blue-600" wire:loading wire:target="iAgreementFile">Uploading…</p>
                                    @endif
                                    @error('iAgreementFile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endif
                    </section>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeInvoice" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveInvoice" wire:loading.attr="disabled" wire:target="saveInvoice,iAgreementFile"
                        class="px-4 py-2 text-sm font-medium text-gray-800 border border-gray-300 hover:bg-gray-50 rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveInvoice">{{ $invoiceId ? 'Save' : 'Save Draft' }}</span>
                        <span wire:loading wire:target="saveInvoice">Saving…</span>
                    </button>
                    <button wire:click="saveInvoice(true)" wire:loading.attr="disabled" wire:target="saveInvoice,iAgreementFile"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        Save &amp; Send
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ AN INVOICE, OPEN TO READ ══════════ --}}
    @if ($view)
        @php $billTo = $view->bill_to ?: []; @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-semibold text-gray-900">{{ $view->number }}</h2>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls[$view->status] ?? '' }}">{{ $statuses[$view->status] ?? $view->status }}</span>
                                @if ($view->isOverdue())
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Overdue</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $view->schoolName() }} · {{ $inr($view->total) }}</p>
                        </div>
                        <button wire:click="closeView" class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button wire:click="downloadPdf({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-300 hover:bg-gray-50 rounded-md">Download PDF</button>
                        @if ($view->status !== 'cancelled')
                            <button wire:click="openSend({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">{{ $view->sent_at ? 'Send again' : 'Send to school' }}</button>
                        @endif
                        @if ($view->canEdit())
                            <button wire:click="openPaid({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium text-emerald-700 border border-emerald-200 hover:bg-emerald-50 rounded-md">Mark paid</button>
                            <button wire:click="openInvoice({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-300 hover:bg-gray-50 rounded-md">Edit</button>
                        @elseif ($view->status === 'paid')
                            <button wire:click="markUnpaid({{ $view->id }})" wire:confirm="Mark this invoice unpaid again?" class="px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-300 hover:bg-gray-50 rounded-md">Mark unpaid</button>
                        @endif
                        @if ($view->hasAgreement() && $view->status !== 'cancelled')
                            <button wire:click="toggleSigned({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium border rounded-md {{ $view->agreement_signed_at ? 'text-emerald-700 border-emerald-200 bg-emerald-50' : 'text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                                {{ $view->agreement_signed_at ? '✓ Agreement signed' : 'Mark agreement signed' }}
                            </button>
                        @endif
                        <span class="ml-auto"></span>
                        @if ($view->status === 'draft')
                            <button wire:click="confirmDeleteInvoice({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-md">Delete draft</button>
                        @elseif (in_array($view->status, ['sent'], true))
                            <button wire:click="confirmCancel({{ $view->id }})" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-md">Cancel invoice</button>
                        @endif
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Bill to</p>
                            <p class="font-semibold text-gray-900 mt-1">{{ $billTo['name'] ?? $view->schoolName() }}</p>
                            @if (!empty($billTo['address']))<p class="text-gray-600 whitespace-pre-line">{{ $billTo['address'] }}</p>@endif
                            @if (!empty($billTo['email']) || !empty($billTo['phone']))
                                <p class="text-gray-600">{{ implode(' · ', array_filter([$billTo['email'] ?? null, $billTo['phone'] ?? null])) }}</p>
                            @endif
                        </div>
                        <div class="sm:text-right">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Dates</p>
                            <p class="text-gray-700 mt-1">Invoice {{ $view->invoice_date?->format('d M Y') }}</p>
                            @if ($view->due_date)<p class="text-gray-700">Due {{ $view->due_date->format('d M Y') }}</p>@endif
                            @if ($view->status === 'paid')
                                <p class="text-emerald-700 font-medium">Paid {{ $view->paid_at?->format('d M Y') }}{{ $view->payment_mode ? ' · ' . $view->payment_mode : '' }}</p>
                                @if ($view->payment_reference)<p class="text-xs text-gray-500">Ref. {{ $view->payment_reference }}</p>@endif
                            @endif
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">Item</th>
                                    <th class="px-3 py-2 text-right font-semibold">Qty</th>
                                    <th class="px-3 py-2 text-right font-semibold">Rate</th>
                                    <th class="px-3 py-2 text-right font-semibold">GST</th>
                                    <th class="px-3 py-2 text-right font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($view->items as $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <p class="font-medium text-gray-900">{{ $item->title }}</p>
                                            @if ($item->description)<p class="text-xs text-gray-500">{{ $item->description }}</p>@endif
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">{{ $qty($item->quantity) }}{{ $item->unit ? ' ' . $item->unit : '' }}</td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">{{ $inr($item->unit_price) }}</td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">{{ $qty($item->gst_rate) }}%</td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">{{ $inr($item->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $vs = \App\Services\StoreInvoices::totals($view->items->map(fn ($i) => ['quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'gst_rate' => $i->gst_rate])->all(), $view->discount);
                    @endphp
                    <div class="flex justify-end">
                        <div class="w-full sm:w-72 text-sm space-y-1.5">
                            <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span>{{ $inr($vs['subtotal']) }}</span></div>
                            @if ($vs['discount'] > 0)
                                <div class="flex justify-between"><span class="text-gray-600">Discount</span><span>− {{ $inr($vs['discount']) }}</span></div>
                            @endif
                            @foreach ($vs['by_rate'] as $rate => $tax)
                                <div class="flex justify-between"><span class="text-gray-600">GST @ {{ $rate }}%</span><span>{{ $inr($tax) }}</span></div>
                            @endforeach
                            <div class="flex justify-between border-t border-gray-300 pt-2 text-base"><span class="font-semibold">Total</span><span class="font-bold">{{ $inr($view->total) }}</span></div>
                        </div>
                    </div>

                    @if ($view->notes)
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Notes</p>
                            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $view->notes }}</p>
                        </div>
                    @endif

                    @if ($view->hasAgreement())
                        <div class="border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                                <p class="text-sm font-semibold text-gray-900">{{ $view->agreement_title ?: 'Agreement' }}</p>
                                <span class="text-xs font-medium {{ $view->agreement_signed_at ? 'text-emerald-700' : 'text-gray-500' }}">
                                    {{ $view->agreement_signed_at ? 'Signed ' . $view->agreement_signed_at->format('d M Y') : 'Not signed yet' }}
                                </span>
                            </div>
                            <div class="px-4 py-3 space-y-3">
                                @if (trim((string) $view->agreement_body) !== '')
                                    <p class="text-sm text-gray-700 whitespace-pre-line max-h-72 overflow-y-auto">{{ \App\Services\StoreInvoices::agreementText($view) }}</p>
                                @endif
                                @if ($view->agreement_file_url)
                                    <a href="{{ $view->agreement_file_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        {{ $view->agreement_file_name ?: 'Agreement document' }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="text-xs text-gray-500 space-y-1 border-t border-gray-100 pt-4">
                        <p>Made {{ $view->created_at?->format('d M Y, g:i A') }}</p>
                        @if ($view->sent_at)
                            <p>Emailed {{ $view->sent_at->format('d M Y, g:i A') }} to {{ $view->sent_to }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ SETTINGS SLIDE-IN ══════════ --}}
    @if ($showSettings)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSettings"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Invoice Settings</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Who SuperLMS is on an invoice, where schools pay, and the default terms and agreement</p>
                    </div>
                    <button wire:click="closeSettings" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $lb }}">Business name <span class="text-red-500">*</span></label>
                            <input wire:model="settings.store_seller_name" type="text" class="{{ $in }}">
                            @error('settings.store_seller_name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lb }}">GSTIN</label>
                            <input wire:model="settings.store_seller_gstin" type="text" maxlength="15" placeholder="09ABCDE1234F1Z5" class="{{ $in }} uppercase">
                            @error('settings.store_seller_gstin')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="{{ $lb }}">Address</label>
                        <textarea wire:model="settings.store_seller_address" rows="2" class="{{ $in }}"></textarea>
                        @error('settings.store_seller_address')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="{{ $lb }}">Phone</label>
                            <input wire:model="settings.store_seller_phone" type="text" class="{{ $in }}">
                            @error('settings.store_seller_phone')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lb }}">Email</label>
                            <input wire:model="settings.store_seller_email" type="email" class="{{ $in }}">
                            @error('settings.store_seller_email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="{{ $lb }}">Number prefix <span class="text-red-500">*</span></label>
                            <input wire:model="settings.store_invoice_prefix" type="text" maxlength="12" class="{{ $in }}">
                            <p class="mt-1 text-xs text-gray-400">{{ ($settings['store_invoice_prefix'] ?? 'INV') ?: 'INV' }}/{{ \App\Services\StoreInvoices::financialYear(now()) }}/0001</p>
                            @error('settings.store_invoice_prefix')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="{{ $lb }}">How to pay</label>
                        <textarea wire:model="settings.store_payment_details" rows="3" placeholder="Bank, account number, IFSC, UPI id…" class="{{ $in }}"></textarea>
                        @error('settings.store_payment_details')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $lb }}">Terms</label>
                        <textarea wire:model="settings.store_invoice_terms" rows="3" class="{{ $in }}"></textarea>
                        @error('settings.store_invoice_terms')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-sm font-semibold text-gray-900">Default agreement</p>
                        <div class="mb-3"></div>
                        <label class="{{ $lb }}">Title</label>
                        <input wire:model="settings.store_agreement_title" type="text" class="{{ $in }}">
                        @error('settings.store_agreement_title')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        <label class="{{ $lb }} mt-3">Agreement</label>
                        <textarea wire:model="settings.store_agreement_body" rows="12" class="{{ $in }} font-mono text-[13px] leading-relaxed"></textarea>
                        <p class="mt-1 text-xs text-gray-400">These fill themselves in: {{ implode(' ', \App\Services\StoreInvoices::PLACEHOLDERS) }}</p>
                        @error('settings.store_agreement_body')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeSettings" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSettings" wire:loading.attr="disabled" wire:target="saveSettings"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSettings">Save Settings</span>
                        <span wire:loading wire:target="saveSettings">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ SEND POPUP ══════════ --}}
    @if ($sendId)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4 lms-cover" wire:click.self="closeSend">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
                <h3 class="text-sm font-semibold text-gray-900">Email the invoice to the school</h3>
                <p class="text-xs text-gray-500 mt-0.5">The PDF goes attached{{ ($sendInvoice = \App\Models\Store\StoreInvoice::find($sendId))?->hasAgreement() ? ', with the agreement' : '' }}, and a link to open it.</p>
                <label class="block text-xs font-medium text-gray-700 mt-4 mb-1">To</label>
                <input wire:model="sendTo" type="text" placeholder="school@example.com, principal@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                @error('sendTo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                <label class="block text-xs font-medium text-gray-700 mt-3 mb-1">Message</label>
                <textarea wire:model="sendMessage" rows="3" placeholder="Leave empty for the usual note" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                @error('sendMessage')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                <div class="mt-4 flex items-center gap-2">
                    <button wire:click="closeSend" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg">Cancel</button>
                    <button wire:click="send" wire:loading.attr="disabled" wire:target="send" class="flex-1 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg disabled:opacity-60">
                        <span wire:loading.remove wire:target="send">Send</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ MARK PAID POPUP ══════════ --}}
    @if ($paidId)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4 lms-cover" wire:click.self="closePaid">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-sm font-semibold text-gray-900">Mark the invoice paid</h3>
                <p class="text-xs text-gray-500 mt-0.5">The money the school paid for it.</p>
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Paid on</label>
                        <input wire:model="paidDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        @error('paidDate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Mode</label>
                        <select wire:model="paidMode" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            @foreach (\App\Services\StoreInvoices::PAYMENT_MODES as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="block text-xs font-medium text-gray-700 mt-3 mb-1">Reference</label>
                <input wire:model="paidReference" type="text" placeholder="UTR / cheque no." class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                @error('paidReference')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                <div class="mt-4 flex items-center gap-2">
                    <button wire:click="closePaid" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg">Cancel</button>
                    <button wire:click="markPaid" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg">Mark Paid</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ CONFIRMS ══════════ --}}
    @foreach ([
        ['show' => $deleteProductId, 'title' => 'Delete this product?', 'text' => 'Its images go too. Invoices already made keep their lines.', 'cancel' => '$set(\'deleteProductId\', null)', 'ok' => 'deleteProduct', 'label' => 'Delete'],
        ['show' => $deleteInvoiceId, 'title' => 'Delete this draft?', 'text' => 'It was never sent, so it simply goes.', 'cancel' => '$set(\'deleteInvoiceId\', null)', 'ok' => 'deleteInvoice', 'label' => 'Delete'],
        ['show' => $cancelId, 'title' => 'Cancel this invoice?', 'text' => 'It stays in the list as Cancelled and its link stops opening. Its number is not used again.', 'cancel' => '$set(\'cancelId\', null)', 'ok' => 'cancelInvoice', 'label' => 'Cancel Invoice'],
    ] as $c)
        @if ($c['show'])
            <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4 lms-cover" wire:click.self="{{ $c['cancel'] }}">
                <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $c['title'] }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $c['text'] }}</p>
                    <div class="mt-4 flex items-center gap-2">
                        <button wire:click="{{ $c['cancel'] }}" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg">Keep</button>
                        <button wire:click="{{ $c['ok'] }}" class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">{{ $c['label'] }}</button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
