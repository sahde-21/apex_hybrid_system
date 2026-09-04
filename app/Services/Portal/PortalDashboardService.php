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
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
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
     * @return Collection<int, array<string, mixed>>
     */
    public function recentActivity(PortalCustomer $customer, int $limit = 8): Collection
    {
        $contactId = $customer->contact_id;

        return collect(array_merge(
            $this->orderActivity($contactId),
            $this->invoiceActivity($contactId),
            $this->ticketActivity($contactId),
        ))
            ->sortByDesc('at')
            ->take($limit)
            ->values()
            ->map(fn (array $row): array => $this->normalizeActivityRow($row));
    }

    /**
     * @return array<int, array{type: string, label: string, status: string, at: Carbon, url: string}>
     */
    private function orderActivity(int $contactId): array
    {
        return SaleOrder::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (SaleOrder $o) => $this->activityRow(
                'order',
                $o->reference_number,
                $o->status->value,
                $o->created_at,
                route('portal.orders.show', $o),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{type: string, label: string, status: string, at: Carbon, url: string}>
     */
    private function invoiceActivity(int $contactId): array
    {
        return Invoice::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Invoice $i) => $this->activityRow(
                'invoice',
                $i->reference_number,
                $i->status->value,
                $i->created_at,
                route('portal.invoices.show', $i),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{type: string, label: string, status: string, at: Carbon, url: string}>
     */
    private function ticketActivity(int $contactId): array
    {
        return Ticket::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Ticket $t) => $this->activityRow(
                'ticket',
                $t->subject,
                $t->status->value,
                $t->created_at,
                route('portal.tickets.show', $t),
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array{type: string, label: string, status: string, at: Carbon, url: string}  $row
     * @return array<string, mixed>
     */
    private function normalizeActivityRow(array $row): array
    {
        return [
            'type' => $row['type'],
            'label' => $row['label'],
            'status' => $row['status'],
            'at' => $row['at'],
            'url' => $row['url'],
        ];
    }

    /**
     * @return array{type: string, label: string, status: string, at: Carbon, url: string}
     */
    private function activityRow(string $type, string $label, string $status, Carbon $at, string $url): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'status' => $status,
            'at' => $at,
            'url' => $url,
        ];
    }
}
