<?php

namespace App\Services\Portal;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\Contact;
use App\Models\LoyaltyBalance;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyRedemption;
use App\Models\PortalCustomer;
use App\Models\PortalNotification;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketReply;
use App\Services\Notifications\NotificationCenterService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PortalService
{
    public function notify(PortalCustomer $customer, string $type, string $title, ?string $body = null, ?string $actionUrl = null): PortalNotification
    {
        return $customer->portalNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    public function updateProfile(PortalCustomer $customer, array $data, ?UploadedFile $avatar = null): PortalCustomer
    {
        return DB::transaction(function () use ($customer, $data, $avatar) {
            if ($avatar) {
                $data['avatar_path'] = $avatar->store('portal-avatars', 'public');
            }

            $customer->update(collect($data)->only([
                'name', 'phone', 'locale', 'password', 'avatar_path',
            ])->filter(fn ($v) => $v !== null)->all());

            /** @var Contact $contact */
            $contact = $customer->contact;
            $contact->update([
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $data['address'] ?? $contact->address,
            ]);

            return $customer->fresh(['contact']);
        });
    }

    public function createTicket(PortalCustomer $customer, array $data, array $files = []): Ticket
    {
        return DB::transaction(function () use ($customer, $data, $files) {
            $ticket = Ticket::query()->create([
                'reference_number' => 'TKT-'.strtoupper(Str::random(8)),
                'contact_id' => $customer->contact_id,
                'subject' => $data['subject'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'description' => $data['description'],
            ]);

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $this->storeAttachment($ticket, $file, $customer);
                }
            }

            $this->notify(
                $customer,
                'ticket.created',
                __('scf.portal.ticket_created_title'),
                $ticket->subject,
                route('portal.tickets.show', $ticket),
            );

            app(NotificationCenterService::class)->notifyByPermission(
                permission: 'tickets.read',
                event: 'portal.ticket.created',
                title: __('Portal ticket: :subject', ['subject' => $ticket->subject]),
                body: $customer->name.' · '.$ticket->reference_number,
                category: NotificationCategory::Information,
                priority: NotificationPriority::High,
                module: 'tickets',
                actionUrl: Route::has('tickets.show') ? route('tickets.show', $ticket) : null,
                meta: ['ticket_id' => $ticket->id, 'portal_customer_id' => $customer->id],
            );

            return $ticket;
        });
    }

    public function replyToTicket(Ticket $ticket, PortalCustomer $customer, string $body, array $files = []): TicketReply
    {
        return DB::transaction(function () use ($ticket, $customer, $body, $files) {
            $reply = $ticket->replies()->create([
                'author_type' => PortalCustomer::class,
                'author_id' => $customer->id,
                'body' => $body,
                'is_internal' => false,
            ]);

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $this->storeAttachment($ticket, $file, $customer, $reply);
                }
            }

            if ($ticket->status?->value === 'closed' || $ticket->status?->value === 'resolved') {
                $ticket->update(['status' => 'open']);
            }

            return $reply;
        });
    }

    public function redeemLoyalty(PortalCustomer $customer, LoyaltyProgram $program, float $points, string $rewardLabel): LoyaltyRedemption
    {
        return DB::transaction(function () use ($customer, $program, $points, $rewardLabel) {
            /** @var LoyaltyBalance $balance */
            $balance = LoyaltyBalance::query()->firstOrCreate(
                [
                    'contact_id' => $customer->contact_id,
                    'loyalty_program_id' => $program->id,
                ],
                ['points' => 0],
            );

            abort_if((float) $balance->points < $points, 422, __('scf.portal.insufficient_points'));

            $balance->update(['points' => (float) $balance->points - $points]);

            $redemption = LoyaltyRedemption::query()->create([
                'contact_id' => $customer->contact_id,
                'loyalty_program_id' => $program->id,
                'points' => $points,
                'reward_label' => $rewardLabel,
                'status' => 'completed',
            ]);

            $this->notify(
                $customer,
                'loyalty.redeemed',
                __('scf.portal.loyalty_redeemed_title'),
                $rewardLabel,
                route('portal.loyalty.index'),
            );

            return $redemption;
        });
    }

    protected function storeAttachment(
        Ticket $ticket,
        UploadedFile $file,
        PortalCustomer $customer,
        ?TicketReply $reply = null,
    ): TicketAttachment {
        $path = $file->store('portal-tickets/'.$ticket->id, 'public');

        return TicketAttachment::query()->create([
            'ticket_id' => $ticket->id,
            'ticket_reply_id' => $reply?->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by_type' => PortalCustomer::class,
            'uploaded_by_id' => $customer->id,
        ]);
    }
}
