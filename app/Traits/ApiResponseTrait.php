<?php

namespace App\Traits;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiResponseTrait
{
    /**
     * Return a successful API response.
     */
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    /**
     * Return an error API response.
     */
    protected function error(string $message = 'Something went wrong.', mixed $errors = null, int $status = 400): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }

    /**
     * Return a paginated API response.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], $message);
    }

    /**
     * Return a collection API response.
     */
    protected function collection(Collection|array $items, string $message = 'OK'): JsonResponse
    {
        return $this->success($items, $message);
    }
}
