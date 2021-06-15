<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Nubium\ApiFrame\Http\ApiRequest;
use Nubium\ApiFrame\Http\ApiResponse;

class MiddlewareRequestHandler
{
	/** @var RequestHandlerInterface */
	private $requestHandler;


	public function __construct(RequestHandlerInterface $handler)
	{
		$this->requestHandler = $handler;
	}


	public function handle(ApiRequest $request): ApiResponse
	{
		$handlerResponse = $this->requestHandler->handle($request);
		if (!$handlerResponse instanceof ApiResponse) {
			throw new RuntimeException('Invalid handler response type ' . get_class($handlerResponse) . '. Expected ' . ApiResponse::class);
		}

		return $handlerResponse;
	}
}
