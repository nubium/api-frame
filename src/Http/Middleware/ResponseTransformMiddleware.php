<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Nubium\ApiFrame\Http\ApiRequest;
use Nubium\ApiFrame\Http\ApiResponse;
use Nubium\ApiFrame\Http\IResponseTransformer;
use Nubium\ApiFrame\Middleware\MiddlewareRequestHandler;
use Nubium\ApiFrame\Middleware\MiddlewareTrait;

class ResponseTransformMiddleware implements MiddlewareInterface
{
	use MiddlewareTrait;


	private IResponseTransformer $transformer;


	public function __construct(IResponseTransformer $transformer)
	{
		$this->transformer = $transformer;
	}


	protected function doProcess(ApiRequest $request, MiddlewareRequestHandler $next): ApiResponse
	{
		$response = $next->handle($request);
		$transformedResponse = $this->transformer->transformResponse($response);

		return $transformedResponse;
	}
}
