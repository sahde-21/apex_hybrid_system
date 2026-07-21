<?php

namespace App\Support\Analytics;

use App\Models\User;
use App\Support\Bi\BiFilter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class AnalyticsFilter
{
    public function __construct(
        public readonly BiFilter $bi,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(BiFilter::fromRequest($request));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(BiFilter::fromArray($input));
    }

    public static function default(): self
    {
        $days = (int) config('intelligence.default_date_range_days', 30);

        return self::fromArray([
            'from' => now()->subDays($days)->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function cacheKey(User $user, string $suffix = ''): string
    {
        return config('intelligence.cache_prefix').md5(json_encode([
            $this->bi->toArray(),
            $suffix,
            $user->id,
            app()->getLocale(),
        ]));
    }

    public function from(): CarbonImmutable
    {
        return $this->bi->from;
    }

    public function to(): CarbonImmutable
    {
        return $this->bi->to;
    }

    public function branchId(): ?int
    {
        return $this->bi->branchId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->bi->toArray();
    }
}
