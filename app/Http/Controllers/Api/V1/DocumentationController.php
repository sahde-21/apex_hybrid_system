<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Support\Api\OpenApiSpec;
use Illuminate\Http\JsonResponse;

class DocumentationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            OpenApiSpec::build(),
            __('scf.api.docs_retrieved'),
        );
    }
}
