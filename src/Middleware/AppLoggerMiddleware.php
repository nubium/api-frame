<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Middleware;

use Nubium\ApiFrame\Http\ApiRequest;
use Nubium\ApiFrame\Http\ApiResponse;
use Psr\Http\Server\MiddlewareInterface;
use Nubium\AppLogger\IAppLogger;

class AppLoggerMiddleware implements MiddlewareInterface
{
	use MiddlewareTrait;


	public function __construct(private IAppLogger $appLogger) {}


	protected function doProcess(ApiRequest $request, MiddlewareRequestHandler $next): ApiResponse
	{
		return $next->handle($request)->withHeaders($this->appLogger->getHttpHeaders());
	}
}
