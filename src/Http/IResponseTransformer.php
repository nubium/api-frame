<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

use Throwable;

interface IResponseTransformer
{
	public function transformResponse(ApiResponse $response): ApiResponse;

	public function transformError(ApiResponse $response, Throwable $error): ApiResponse;
}
