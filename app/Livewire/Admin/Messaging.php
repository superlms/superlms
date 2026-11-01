<?php

namespace App\Livewire\Admin;

use App\Jobs\SendMessagingCampaign;
use App\Models\Messaging\MessagingCampaign;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Services\MessagingWallet;
use App\Services\SchoolEmail;
use App\Services\SchoolWhatsApp;
use App\Services\WhatsAppService;
use App\Support\MessagingAudience;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Messaging: the school's messaging wallet and what it sends with it.
 *
 *   Wallet  the balance, Add funds (the school pays SuperLMS, then asks for
 *           the money to be put in; the Super Admin approves it) and the
 *           wallet's statement
 *   Send    a message to the school's students — all, a class or a
 *           section — its teachers, or everyone, by WhatsApp or email, as
 *           SuperLMS has turned them on for the school; paid from the wallet
 *   Sent    the messages sent, and how far each got
 *   Usage   the school's WhatsApp analytics and its emails
 */
class Messaging extends Component
{
    use WireUiActions, WithPagination;
    use \App\Livewire\Concerns\HandlesWhatsAppAnalytics;

    public const TABS = ['wallet' => 'Wallet', 'send' => 'Send', 'sent' => 'Sent', 'usage' => 'Usage'];

    /** The longest message each way. */
    public const MAX = ['whatsapp' => 900, 'email' => 5000];

    #[Url(history: true, except: 'wallet')]
    public string $tab = 'wallet';

    // ─── Add funds ──────────────────────────────────────────────────────────
    public bool   $showFunds  = false;
    public        $fAmount    = '';
    public string $fMode      = 'UPI';
    public string $fReference = '';
    public string $fNote      = '';
    public string $fDate      = '';

    // ─── Send ───────────────────────────────────────────────────────────────
    public string $channel    = 'whatsapp';
    public string $audience   = 'students';
    public string $standardId = '';
    public string $sectionId  = '';
    public string $subject    = '';
    public string $body       = '';
    public bool   $confirming = false;

    public function mount(): void
    {
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'wallet';
        }
        // The first way the school has on.
        $channels = $this->channels();
        if (!$channels['whatsapp']['on'] && $channels['email']['on']) {
            $this->channel = 'email';
        }
    }

    protected function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    protected function whatsAppOrgId(): ?int
    {
        return $this->orgId() ?: null;
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab        = $tab;
            $this->confirming = false;
            $this->resetPage();
        }
    }

    /** WhatsApp and email: whether SuperLMS has turned each on for the school, and its rate. */
    public function channels(): array
    {
        $orgId = $this->orgId();

        return [
            'whatsapp' => ['label' => 'WhatsApp', 'on' => SchoolWhatsApp::enabled($orgId, 'notice'), 'rate' => SchoolWhatsApp::rate()],
            'email'    => ['label' => 'Email', 'on' => SchoolEmail::enabled($orgId), 'rate' => MessagingWallet::emailRate()],
        ];
    }

    // ─── Add funds ──────────────────────────────────────────────────────────

    public function openFunds(): void
    {
        $this->resetValidation();
        $this->fAmount    = '';
        $this->fMode      = 'UPI';
        $this->fReference = '';
        $this->fNote      = '';
        $this->fDate      = now()->toDateString();
        $this->showFunds  = true;
    }

    public function closeFunds(): void
    {
        $this->showFunds = false;
    }

    public function requestFunds(): void
    {
        if (!MessagingWallet::tracked()) {
            return;
        }

        $this->validate([
            'fAmount'    => 'required|numeric|min:1|max:1000000',
            'fMode'      => 'required|string|max:40',
            'fReference' => 'required|string|max:120',
            'fNote'      => 'nullable|string|max:500',
            'fDate'      => 'required|date|before_or_equal:today',
        ], [], [
            'fAmount' => 'amount', 'fMode' => 'paid by', 'fReference' => 'reference / UTR', 'fNote' => 'note', 'fDate' => 'date paid',
        ]);

        MessagingWallet::request($this->orgId(), (float) $this->fAmount, $this->fMode, trim($this->fReference), trim($this->fNote), Auth::id(), $this->fDate);

        $this->showFunds = false;
        $this->notification()->success('₹' . number_format((float) $this->fAmount, 2) . ' sent to SuperLMS to approve. It is put in your wallet once approved.');
    }

    // ─── Send ───────────────────────────────────────────────────────────────

    public function updatedAudience(): void
    {
        $this->standardId = '';
        $this->sectionId  = '';
        $this->confirming = false;
    }

    public function updatedStandardId(): void
    {
        $this->sectionId  = '';
        $this->confirming = false;
    }

    public function updatedChannel(): void
    {
        $this->confirming = false;
        $this->resetValidation();
    }

    /** Who the message reaches, and how many of them it can reach on the way picked. */
    public function preview(): array
    {
        $orgId      = $this->orgId();
        $recipients = MessagingAudience::recipients($orgId, $this->audience, (int) $this->standardId ?: null, (int) $this->sectionId ?: null);
        $reachable  = count(array_filter($recipients, fn ($r) => $this->channel === 'whatsapp'
            ? WhatsAppService::normalizePhone($r['phone']) !== null
            : (bool) filter_var((string) $r['email'], FILTER_VALIDATE_EMAIL)));
        $rate = $this->channels()[$this->channel]['rate'] ?? 0;

        return [
            'total'     => count($recipients),
            'reachable' => $reachable,
            'rate'      => $rate,
            'cost'      => round($reachable * $rate, 2),
            'label'     => MessagingAudience::label($orgId, $this->audience, (int) $this->standardId ?: null, (int) $this->sectionId ?: null),
        ];
    }

    private function validateMessage(): void
    {
        $this->validate([
            'channel'    => 'required|in:whatsapp,email',
            'audience'   => 'required|in:' . implode(',', array_keys(MessagingAudience::AUDIENCES)),
            'standardId' => 'nullable|integer',
            'sectionId'  => 'nullable|integer',
            'subject'    => ($this->channel === 'email' ? 'required' : 'nullable') . '|string|max:150',
            'body'       => 'required|string|min:5|max:' . self::MAX[$this->channel === 'email' ? 'email' : 'whatsapp'],
        ], [], ['standardId' => 'class', 'sectionId' => 'section', 'body' => 'message']);

        if ($this->standardId !== '' && !Standard::where('organization_id', $this->orgId())->whereKey((int) $this->standardId)->exists()) {
            $this->addError('standardId', 'Pick one of the school\'s classes.');
        }
        if ($this->sectionId !== '' && !Section::where('standard_id', (int) $this->standardId)->whereKey((int) $this->sectionId)->exists()) {
            $this->addError('sectionId', 'Pick a section of the class.');
        }
        if (!($this->channels()[$this->channel]['on'] ?? false)) {
            $this->addError('channel', ($this->channel === 'email' ? 'Email' : 'WhatsApp') . ' messaging is not on for your school — ask SuperLMS to turn it on.');
        }
    }

    /** Send's first press: the message is checked and its cost shown. */
    public function review(): void
    {
        $this->validateMessage();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $p = $this->preview();
        if ($p['reachable'] === 0) {
            $this->addError('audience', 'Nobody here has ' . ($this->channel === 'email' ? 'an email address' : 'a mobile number') . ' to send to.');
            return;
        }
        if (MessagingWallet::prepaid() && MessagingWallet::balance($this->orgId()) + 0.0001 < $p['cost']) {
            $this->addError('body', 'Your wallet has ₹' . number_format(MessagingWallet::balance($this->orgId()), 2)
                . ' and this message costs ₹' . number_format($p['cost'], 2) . '. Add funds first.');
            return;
        }

        $this->confirming = true;
    }

    public function cancelReview(): void
    {
        $this->confirming = false;
    }

    /** The second press: the message is queued and goes out a part at a time. */
    public function send(): void
    {
        $this->validateMessage();
        if ($this->getErrorBag()->isNotEmpty() || !MessagingWallet::tracked()) {
            $this->confirming = false;
            return;
        }

        $p = $this->preview();
        if ($p['reachable'] === 0) {
            $this->confirming = false;
            return;
        }

        $campaign = MessagingCampaign::create([
            'organization_id' => $this->orgId(),
            'channel'         => $this->channel,
            'audience'        => $this->audience,
            'standard_id'     => $this->audience === 'students' && $this->standardId !== '' ? (int) $this->standardId : null,
            'section_id'      => $this->audience === 'students' && $this->sectionId !== '' ? (int) $this->sectionId : null,
            'audience_label'  => $p['label'],
            'subject'         => $this->channel === 'email' ? trim($this->subject) : null,
            'body'            => trim($this->body),
            'recipients'      => $p['total'],
            'rate'            => $p['rate'],
            'status'          => 'queued',
            'created_by'      => Auth::id(),
        ]);

        SendMessagingCampaign::dispatch($campaign->id);

        $this->reset(['subject', 'body', 'confirming']);
        $this->tab = 'sent';
        $this->resetPage();
        $this->notification()->success('Your message is on its way to ' . number_format($p['reachable']) . ' '
            . ($p['reachable'] === 1 ? 'person' : 'people') . '.');
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId   = $this->orgId();
        $tracked = MessagingWallet::tracked();
        $data    = [
            'tracked'  => $tracked,
            'channels' => $this->channels(),
            'balance'  => $tracked ? MessagingWallet::balance($orgId) : 0.0,
            'month'    => $tracked ? MessagingWallet::usage($orgId, now()->startOfMonth()) : null,
            'prepaid'  => MessagingWallet::prepaid(),
            'pending'  => $tracked ? \App\Models\Messaging\MessagingWalletTransaction::where('organization_id', $orgId)->pending()->sum('amount') : 0,
        ];

        if ($this->tab === 'wallet' && $tracked) {
            $data['rows'] = MessagingWallet::statement($orgId);
        }

        if ($this->tab === 'send') {
            $data['standards'] = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name']);
            $data['sections']  = $this->standardId !== '' ? Section::where('standard_id', (int) $this->standardId)->orderBy('name')->get(['id', 'name']) : collect();
            $data['preview']   = $this->preview();
        }

        if ($this->tab === 'sent' && $tracked) {
            $data['campaigns'] = MessagingCampaign::with('creator:id,name')
                ->where('organization_id', $orgId)
                ->latest('id')
                ->paginate(20);
        }

        if ($this->tab === 'usage') {
            $data['email'] = SchoolEmail::tracked() ? SchoolEmail::summary($orgId, 15) : null;
        }

        return view('livewire.admin.messaging', $data);
    }
}
