<?php

namespace App\Support;

/**
 * Resolves the currently authenticated user for school scoping.
 *
 * Only the already-resolved user is read. Calling `auth('sanctum')->user()`
 * directly from inside a global scope recurses infinitely while the sanctum
 * guard is itself resolving the bearer token (the tokenable lookup runs a
 * query that re-enters the scope), so we first check `hasUser()` and return
 * the cached user only.
 */
class SchoolContext
{
    /**
     * The currently authenticated user, or null when not yet resolved.
     */
    public static function user(): mixed
    {
        if (auth('sanctum')->hasUser()) {
            return auth('sanctum')->user();
        }

        if (auth()->hasUser()) {
            return auth()->user();
        }

        return null;
    }
}