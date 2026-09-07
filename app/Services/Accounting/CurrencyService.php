<?php

namespace App\Services\Accounting;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurrencyService
{
    public function __construct(
        protected AccountingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Currency
    {
        return DB::transaction(function () use ($user, $data) {
            $isBase = (bool) ($data['is_base'] ?? false);

            if ($isBase) {
                $this->clearBaseFlags();
            }

            $currency = Currency::query()->create([
                'code' => strtoupper((string) $data['code']),
                'name' => $data['name'],
                'symbol' => $data['symbol'] ?? $data['code'],
                'decimal_places' => (int) ($data['decimal_places'] ?? 2),
                'is_base' => $isBase,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->log('currency.created', $currency, $user);

            return $currency;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Currency $currency, User $user, array $data): Currency
    {
        return DB::transaction(function () use ($currency, $user, $data) {
            $isBase = array_key_exists('is_base', $data) ? (bool) $data['is_base'] : $currency->is_base;

            if ($currency->is_base && isset($data['is_active']) && ! $data['is_active']) {
                throw ValidationException::withMessages([
                    'is_active' => [__('scf.accounting_engine.cannot_deactivate_base_currency')],
                ]);
            }

            if ($isBase && ! $currency->is_base) {
                $this->clearBaseFlags();
            }

            if ($currency->is_base && array_key_exists('is_base', $data) && ! $data['is_base']) {
                throw ValidationException::withMessages([
                    'is_base' => [__('scf.accounting_engine.cannot_unset_base_currency')],
                ]);
            }

            $currency->update([
                'code' => isset($data['code']) ? strtoupper((string) $data['code']) : $currency->code,
                'name' => $data['name'] ?? $currency->name,
                'symbol' => $data['symbol'] ?? $currency->symbol,
                'decimal_places' => isset($data['decimal_places']) ? (int) $data['decimal_places'] : $currency->decimal_places,
                'is_base' => $isBase,
                'is_active' => $data['is_active'] ?? $currency->is_active,
            ]);

            $this->audit->log('currency.updated', $currency, $user);

            return $currency->refresh();
        });
    }

    public function setBase(Currency $currency, User $user): Currency
    {
        return DB::transaction(function () use ($currency, $user) {
            if (! $currency->is_active) {
                throw ValidationException::withMessages([
                    'currency' => [__('scf.accounting_engine.inactive_currency_cannot_be_base')],
                ]);
            }

            $this->clearBaseFlags();
            $currency->update(['is_base' => true, 'is_active' => true]);
            $this->audit->log('currency.set_base', $currency, $user);

            return $currency->refresh();
        });
    }

    public function delete(Currency $currency, User $user): void
    {
        if ($currency->is_base) {
            throw ValidationException::withMessages([
                'currency' => [__('scf.accounting_engine.cannot_delete_base_currency')],
            ]);
        }

        if ($currency->rates()->exists()) {
            throw ValidationException::withMessages([
                'currency' => [__('scf.accounting_engine.currency_has_rates')],
            ]);
        }

        $this->audit->log('currency.deleted', $currency, $user, ['code' => $currency->code]);
        $currency->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertExchangeRate(Currency $currency, User $user, array $data): ExchangeRate
    {
        if ($currency->is_base) {
            throw ValidationException::withMessages([
                'currency' => [__('scf.accounting_engine.base_currency_no_rates')],
            ]);
        }

        $rate = (float) $data['rate'];
        if ($rate <= 0) {
            throw ValidationException::withMessages([
                'rate' => [__('scf.accounting_engine.exchange_rate_must_be_positive')],
            ]);
        }

        $exchangeRate = ExchangeRate::query()->updateOrCreate(
            [
                'currency_id' => $currency->id,
                'rate_date' => $data['rate_date'],
            ],
            [
                'rate' => $rate,
                'created_by' => $user->id,
            ]
        );

        $this->audit->log('exchange_rate.upserted', $exchangeRate, $user, [
            'currency' => $currency->code,
            'rate_date' => $data['rate_date'],
            'rate' => $rate,
        ]);

        return $exchangeRate;
    }

    public function deleteExchangeRate(ExchangeRate $rate, User $user): void
    {
        $this->audit->log('exchange_rate.deleted', $rate, $user, [
            'currency_id' => $rate->currency_id,
            'rate_date' => $rate->rate_date->toDateString(),
        ]);

        $rate->delete();
    }

    public function rateFor(Currency $currency, string $date): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->where('currency_id', $currency->id)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->first();
    }

    protected function clearBaseFlags(): void
    {
        Currency::query()->where('is_base', true)->update(['is_base' => false]);
    }
}
