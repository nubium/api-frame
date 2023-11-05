<?php declare(strict_types = 1);
namespace Nubium\ApiFrame\Dispatcher;

use Apitte\Core\Exception\Logical\InvalidArgumentException;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Handler\IHandler;
use Apitte\Core\Handler\ServiceCallback;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\Http\RequestAttributes;
use Apitte\Core\Schema\Endpoint;
use Apitte\Core\UI\Controller\IController;
use Psr\Container\ContainerInterface;

class ServiceHandler implements IHandler
{

	/** @var ContainerInterface */
	protected $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function handle(ApiRequest $request, ApiResponse $response): mixed
	{
		// Create and trigger callback
		$endpoint = $this->getEndpoint($request);
		$callback = $this->createCallback($endpoint);
		$response = $callback($request, $response);
		return $response;
	}

	protected function createCallback(Endpoint $endpoint): ServiceCallback
	{
		// Find handler in DI container by class
		$service = $this->getService($endpoint);
		$method = $endpoint->getHandler()->getMethod();

		// Create callback
		return new ServiceCallback($service, $method);
	}

	protected function getEndpoint(ApiRequest $request): Endpoint
	{
		/** @var Endpoint|null $endpoint */
		$endpoint = $request->getAttribute(RequestAttributes::ATTR_ENDPOINT);

		// Validate that we have an endpoint
		if (!$endpoint) {
			throw new InvalidStateException(sprintf('Attribute "%s" is required', RequestAttributes::ATTR_ENDPOINT));
		}

		return $endpoint;
	}

	protected function getService(Endpoint $endpoint): IController
	{
		$class = $endpoint->getHandler()->getClass();
		$service = $this->container->get($class);

		if (!($service instanceof IController)) {
			throw new InvalidArgumentException(sprintf('Controller "%s" must implement "%s"', $class, IController::class));
		}

		return $service;
	}

}
