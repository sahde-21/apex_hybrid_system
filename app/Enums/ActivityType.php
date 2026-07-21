<?php

namespace App\Enums;

enum ActivityType: string
{
    case Comment = 'comment';
    case InternalNote = 'internal_note';
    case Mention = 'mention';
    case Attachment = 'attachment';
    case StatusChange = 'status_change';
    case WorkflowTransition = 'workflow_transition';
    case Approval = 'approval';
    case Rejection = 'rejection';
    case Cancellation = 'cancellation';
    case Conversion = 'conversion';
    case Payment = 'payment';
    case AccountingPosting = 'accounting_posting';
    case Notification = 'notification';
    case SystemEvent = 'system_event';
    case FieldChange = 'field_change';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    public function label(): string
    {
        return __('scf.activity.type_'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Comment => 'chat-bubble-left-right',
            self::InternalNote => 'lock-closed',
            self::Mention => 'at-symbol',
            self::Attachment => 'paper-clip',
            self::StatusChange, self::WorkflowTransition => 'arrows-right-left',
            self::Approval => 'check-circle',
            self::Rejection => 'x-circle',
            self::Cancellation => 'no-symbol',
            self::Conversion => 'arrow-path',
            self::Payment => 'banknotes',
            self::AccountingPosting => 'book-open',
            self::Notification => 'bell',
            self::SystemEvent => 'cog-6-tooth',
            self::FieldChange => 'pencil-square',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Comment => 'sky',
            self::InternalNote => 'amber',
            self::Mention => 'indigo',
            self::Attachment => 'zinc',
            self::StatusChange, self::WorkflowTransition => 'indigo',
            self::Approval => 'green',
            self::Rejection, self::Cancellation => 'red',
            self::Conversion => 'violet',
            self::Payment, self::AccountingPosting => 'emerald',
            self::Notification => 'sky',
            self::SystemEvent => 'zinc',
            self::FieldChange => 'blue',
        };
    }

    public function isUserGenerated(): bool
    {
        return in_array($this, [self::Comment, self::InternalNote], true);
    }
}
