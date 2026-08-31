<?php

namespace App\Services\Supplier;

use App\Models\PortalSupplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;

class SupplierTwoFactorService
{
    public function __construct(
        protected TwoFactorAuthenticationProvider $provider,
    ) {}

    /**
     * @return array{secret: string, qrSvg: string, recoveryCodes: list<string>}
     */
    public function beginSetup(PortalSupplier $supplier): array
    {
        $secret = $this->provider->generateSecretKey();
        $recoveryCodes = array_values(Collection::times(8, fn () => RecoveryCode::generate())->all());

        $supplier->forceFill([
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
            'two_factor_enabled' => false,
        ])->save();

        return [
            'secret' => $secret,
            'qrSvg' => $this->qrSvg($supplier, $secret),
            'recoveryCodes' => $recoveryCodes,
        ];
    }

    public function confirm(PortalSupplier $supplier, string $code): void
    {
        if ($supplier->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => __('Two-factor authentication has not been started.'),
            ]);
        }

        if (! $this->provider->verify(Crypt::decrypt($supplier->two_factor_secret), $code)) {
            throw ValidationException::withMessages([
                'code' => __('The provided two factor authentication code was invalid.'),
            ]);
        }

        $supplier->forceFill(['two_factor_enabled' => true])->save();
    }

    public function disable(PortalSupplier $supplier): void
    {
        $supplier->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function verifyLoginCode(PortalSupplier $supplier, string $code): bool
    {
        if ($supplier->two_factor_secret === null) {
            return false;
        }

        if ($this->provider->verify(Crypt::decrypt($supplier->two_factor_secret), $code)) {
            return true;
        }

        return $this->useRecoveryCode($supplier, $code);
    }

    public function useRecoveryCode(PortalSupplier $supplier, string $code): bool
    {
        if ($supplier->two_factor_recovery_codes === null) {
            return false;
        }

        /** @var list<string> $codes */
        $codes = json_decode(Crypt::decrypt($supplier->two_factor_recovery_codes), true) ?: [];

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $supplier->forceFill([
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(array_values(array_diff($codes, [$code])))),
        ])->save();

        return true;
    }

    protected function qrSvg(PortalSupplier $supplier, string $secret): string
    {
        $url = $this->provider->qrCodeUrl(config('app.name').' Supplier', $supplier->email, $secret);

        $svg = (new \BaconQrCode\Writer(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(192, 0),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd
            )
        ))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }
}
