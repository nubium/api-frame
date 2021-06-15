<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Nubium\ApiFrame\Http\ApiRequest;
use Nubium\ApiFrame\Http\ApiResponse;

trait MiddlewareTrait
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		return $this->doProcess(
			$this->checkApiRequestType($request),
			new MiddlewareRequestHandler($handler)
		);
	}


	protected function checkApiRequestType(ServerRequestInterface $request): ApiRequest
	{
		if (!$request instanceof ApiRequest) {
			throw new RuntimeException('Invalid request type ' . get_class($request) . '. Expected ' . ApiRequest::class);
		}

		return $request;
	}


	protected abstract function doProcess(ApiRequest $request, MiddlewareRequestHandler $next): ApiResponse;
}
