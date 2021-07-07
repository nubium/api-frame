<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Middleware;

use Apitte\Core\Router\IRouter;
use Nubium\ApiFrame\Http\IApiResponseFactory;
use Psr\Http\Server\MiddlewareInterface;
use Nubium\ApiFrame\Http\ApiRequest;
use Nubium\ApiFrame\Http\ApiResponse;
use Nubium\ApiFrame\Middleware\MiddlewareTrait;

class RoutingMiddleware implements MiddlewareInterface
{
	use MiddlewareTrait;


	/** @var IRouter */
	private $router;

	/** @var IApiResponseFactory */
	private $apiResponseFactory;


	public function __construct(IRouter $router, IApiResponseFactory $apiResponseFactory)
	{
		$this->router = $router;
		$this->apiResponseFactory = $apiResponseFactory;
	}


	protected function doProcess(ApiRequest $request, MiddlewareRequestHandler $next): ApiResponse
	{
		$matchedRequest = $this->router->match($request);
		if (!$matchedRequest instanceof ApiRequest) {
			return $this->apiResponseFactory->createApiResponse()
				->withError(
					new RoutingError((string) $request->getUri())
				);
		}

		return $next->handle($matchedRequest);
	}
}
