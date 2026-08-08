<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * List all active sessions (personal access tokens) for the user.
     */
    public function index(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->getKey();

        $sessions = $this->authService->sessions($request->user())->map(function ($token) use ($currentTokenId) {
            return [
                'id' => $token->getKey(),
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'created_at' => $token->created_at?->toISOString(),
                'expires_at' => $token->expires_at?->toISOString(),
                'is_current' => $currentTokenId === $token->getKey(),
            ];
        });

        return $this->collection($sessions, 'Active sessions retrieved.');
    }

    /**
     * Revoke a specific session (token).
     */
    public function destroy(Request $request, int $token): JsonResponse
    {
        $this->authService->revokeToken($request->user(), $token);

        return $this->success(null, 'Session revoked.');
    }

    /**
     * Revoke every session except the current one.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->getKey();

        $count = $this->authService->revokeAllTokens($request->user(), $currentTokenId);

        return $this->success(null, "{$count} session(s) revoked.");
    }
}
