<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    protected function apiResponse(string $message, mixed $data = null, int $statusCode = 200): JsonResponse
    {
        $body = ['statusCode' => $statusCode, 'message' => $message];

        if ($data !== null) {
            $body['data'] = $data;
        }

        return response()->json($body, $statusCode, [], JSON_UNESCAPED_SLASHES);
    }

    protected function paginatedResponse(string $message, LengthAwarePaginator $paginator, mixed $data): JsonResponse
    {
        return response()->json([
            'statusCode' => 200,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'pageSize' => $paginator->perPage(),
                'total' => $paginator->total(),
                'pageCount' => $paginator->lastPage(),
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
