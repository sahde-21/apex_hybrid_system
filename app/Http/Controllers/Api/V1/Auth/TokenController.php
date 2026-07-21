<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Concerns\ResolvesApiPagination;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTokenRequest;
use App\Http\Resources\V1\TokenResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\Api\ApiAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    use ResolvesApiPagination;

    public function __construct(
        protected ApiAuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tokens = $user->tokens()
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::paginated(
            TokenResource::collection($tokens),
            __('Tokens retrieved successfully.'),
        );
    }

    public function store(StoreTokenRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $abilities = $request->input('abilities', config('api.tokens.default_abilities', ['*']));
        $name = $request->string('name')->toString();

        if ($request->filled('client')) {
            $name = sprintf('%s [%s]', $name, $request->string('client')->toString());
        }

        $expiresAt = $request->filled('expires_at')
            ? $request->date('expires_at')
            : $this->defaultExpiration();

        $token = $user->createToken($name, $abilities, $expiresAt);
        $this->audit->tokenCreated($user, $token->accessToken->id, $name);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'access_token' => new TokenResource($token->accessToken),
        ], __('Token created successfully.'), 201);
    }

    public function show(Request $request, string $token): JsonResponse
    {
        $accessToken = $this->findOwnedToken($request, $token);

        return ApiResponse::success(
            new TokenResource($accessToken),
            __('Token retrieved successfully.'),
        );
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        $accessToken = $this->findOwnedToken($request, $token);
        $this->audit->tokenRevoked($request->user(), $accessToken->id);
        $accessToken->delete();

        return ApiResponse::success(null, __('Token revoked successfully.'));
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currentId = $user->currentAccessToken()?->id;

        $user->tokens()
            ->when($currentId !== null, fn ($query) => $query->where('id', '!=', $currentId))
            ->delete();

        return ApiResponse::success(null, __('Other tokens revoked successfully.'));
    }

    private function findOwnedToken(Request $request, string $tokenId): PersonalAccessToken
    {
        /** @var User $user */
        $user = $request->user();

        /** @var PersonalAccessToken $accessToken */
        $accessToken = $user->tokens()->whereKey($tokenId)->firstOrFail();

        return $accessToken;
    }

    private function defaultExpiration(): ?\DateTimeInterface
    {
        $minutes = config('api.tokens.default_expiration_minutes');

        if ($minutes === null || $minutes === '') {
            return null;
        }

        return now()->addMinutes((int) $minutes);
    }
}
