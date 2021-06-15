<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

use Psr\Http\Message\ServerRequestInterface;

interface IApiRequestFactory
{
	public function createApiRequest(ServerRequestInterface $request): ApiRequest;
}
