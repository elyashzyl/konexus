<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The global (spotlight) search endpoint used by the navigation bar.
 *
 * Part 8 – Global Search.
 */
class GlobalSearchController extends ApiController
{
    public function __construct(
        private readonly GlobalSearchService $service,
    ) {}

    /**
     * Search the accessible entities of the authenticated user.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'max:120']]);

        return $this->success(
            $this->service->search($request->user(), $request->string('q')->toString()),
            'Search completed.'
        );
    }
}