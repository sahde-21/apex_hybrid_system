<?php

namespace App\Services\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\TicketStatus;
use App\Models\GiftCard;
use App\Models\Invoice;
use App\Models\LoyaltyBalance;
use App\Models\Payment;
use App\Models\PortalCustomer;
use App\Models\PortalNotification;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class PortalDashboardService
{
    /**
     * @return array{
     *     orders: int,
     *     invoices: int,
     *     payments: int,
     *     outstanding: float,
     *     loyalty_points: float,
     *     open_tickets: int,
     *     quotations: int,
     *     gift_cards: int
     * }
     */
    public function metrics(PortalCustomer $customer): array
    {
        $contactId = $customer->contact_id;

        $outstanding = (float) Invoice::query()
            ->where('contact_id', $contactId)
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->sum('total_amount');

        $paidOnOpen = (float) Payment::query()
            ->where('contact_id', $contactId)
            ->where('type', PaymentType::Incoming)
            ->whereHas('invoice', fn ($q) => $q->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue]))
            ->sum('amount');

        return [
            'orders' => SaleOrder::query()->where('contact_id', $contactId)->count(),
            'invoices' => Invoice::query()->where('contact_id', $contactId)->count(),
            'payments' => Payment::query()->where('contact_id', $contactId)->where('type', PaymentType::Incoming)->count(),
            'outstanding' => max(0, $outstanding - $paidOnOpen),
            'loyalty_points' => (float) LoyaltyBalance::query()->where('contact_id', $contactId)->sum('points'),
            'open_tickets' => Ticket::query()
                ->where('contact_id', $contactId)
                ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])
                ->count(),
            'quotations' => Quotation::query()->where('contact_id', $contactId)->count(),
            'gift_cards' => GiftCard::query()->where('contact_id', $contactId)->where('is_active', true)->count(),
        ];
    }

    /**
     * @return Collection<int, SaleOrder|Invoice|Ticket|PortalNotification>
     */
    public function recentActivity(PortalCustomer $customer, int $limit = 8): Collection
    {
        $contactId = $customer->contact_id;

        $orders = SaleOrder::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (SaleOrder $o) => [
                'type' => 'order',
                'label' => $o->reference_number,
                'status' => $o->status instanceof \BackedEnum ? $o->status->value : (string) $o->status,
                'at' => $o->created_at,
                'url' => route('portal.orders.show', $o),
            ]);

        $invoices = Invoice::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Invoice $i) => [
                'type' => 'invoice',
                'label' => $i->reference_number,
                'status' => $i->status instanceof \BackedEnum ? $i->status->value : (string) $i->status,
                'at' => $i->created_at,
                'url' => route('portal.invoices.show', $i),
            ]);

        $tickets = Ticket::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Ticket $t) => [
                'type' => 'ticket',
                'label' => $t->subject,
                'status' => $t->status instanceof \BackedEnum ? $t->status->value : (string) $t->status,
                'at' => $t->created_at,
                'url' => route('portal.tickets.show', $t),
            ]);

        return $orders
            ->concat($invoices)
            ->concat($tickets)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }
}
