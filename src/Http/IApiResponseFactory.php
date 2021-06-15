<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

use Psr\Http\Message\ResponseInterface;

interface IApiResponseFactory
{
	public function createApiResponse(?ResponseInterface $response = null): ApiResponse;
}
