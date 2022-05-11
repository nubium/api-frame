<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Dispatcher;

use Apitte\Core\Dispatcher\IDispatcher;
use Apitte\Core\Handler\IHandler;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\Http\RequestAttributes;
use Apitte\Core\Schema\Endpoint;
use Nubium\ApiFrame\Http\ResponseAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nubium\ApiFrame\Http\IApiRequestFactory;
use Nubium\ApiFrame\Http\IApiResponseFactory;

class MiddlewareDispatcher implements IDispatcher
{
	private IHandler $handler;
	private IApiRequestFactory $apiRequestFactory;
	private IApiResponseFactory $apiResponseFactory;
	private RequestStore $requestStore;
	/** @var MiddlewareInterface[] */
	private array $middlewares;


	/**
	 * @param MiddlewareInterface[] $middlewares
	 */
	public function __construct(
		IHandler $handler,
		IApiRequestFactory $apiRequestFactory,
		IApiResponseFactory $apiResponseFactory,
		array $middlewares
	)
	{
		$this->handler = $handler;
		$this->apiRequestFactory = $apiRequestFactory;
		$this->apiResponseFactory = $apiResponseFactory;
		$this->middlewares = $middlewares;
		$this->requestStore = new RequestStore();
	}


	public function dispatch(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$apiRequest = $this->apiRequestFactory->createApiRequest($request->getOriginalRequest());
		$apiResponse = $this->apiResponseFactory->createApiResponse($response->getOriginalResponse());

		return $this->callMiddlewareChain($apiRequest, $apiResponse);
	}


	private function callMiddlewareChain(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$next = $this->createCallbackRequestHandler(function (ApiRequest $req) use ($response): ApiResponse {
			/** @var Endpoint $endpoint */
			$endpoint = $req->getAttribute(RequestAttributes::ATTR_ENDPOINT);
			$response = $response->withEndpoint($endpoint);

			if ($this->requestStore->getRequest() !== $req) {
				$response = $response->withAttribute(ResponseAttributes::ATTR_REQUEST, $req);
			}

			/** @var ResponseInterface $handleResponse */
			$handleResponse = $this->handler->handle($req, $response);

			return $this->checkResponseType($handleResponse);
		});

		for ($i = count($this->middlewares) - 1; $i >= 0; --$i) {
			$middleware = $this->middlewares[$i];
			$next = $this->createCallbackRequestHandler(function (ServerRequestInterface $req) use ($middleware, $next): ResponseInterface {
				return $middleware->process($req, $next);
			});
		}

		$chainResponse = $next->handle($request);

		return $this->checkResponseType($chainResponse);
	}

	private function checkResponseType(ResponseInterface $response): ApiResponse
	{
		if (!$response instanceof ApiResponse) {
			throw new \RuntimeException('Response has type ' . get_class($response) . '. Expected ' . ApiResponse::class);
		}

		return $response;
	}

	private function createCallbackRequestHandler(callable $callback): RequestHandlerInterface
	{
		return new class($callback, $this->requestStore) implements RequestHandlerInterface {
			/** @var callable */
			private $callback;
			private RequestStore $requestStore;

			public function __construct(callable $callback, RequestStore $requestStore)
			{
				$this->callback = $callback;
				$this->requestStore = $requestStore;
			}

			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				$this->requestStore->setRequest($request);

				/** @var ApiResponse $response */
				$response = ($this->callback)($request);

				if ($this->requestStore->getRequest() !== $response->getAttribute(ResponseAttributes::ATTR_REQUEST, null)) {
					return $response->withAttribute(ResponseAttributes::ATTR_REQUEST, $this->requestStore->getRequest());
				}

				return $response;
			}
		};
	}
}
