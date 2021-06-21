<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Bridge\Codeception;

use cebe\openapi\spec\OpenApi;
use Codeception\Module\REST;
use Codeception\TestInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\SchemaFactory\JsonFactory;
use Nubium\ApiFrame\Schema\OpenApi\OpenApiSchemaValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function GuzzleHttp\Psr7\parse_query;

class RestModule extends REST
{
	/**
	 * @var array<string, string|null>
	 */
	protected $config = [
		'url' => '',
		'aws' => '',
		'schemaUrl' => null,
	];

	/** @var array<string,string>|null */
	private ?array $routerMap = null;
	private bool $schemaValidationEnabled = true;
	private ?OpenApi $schema = null;


	public function _before(TestInterface $test) : void
	{
		parent::_before($test);
		$groups = $test->getMetadata()->getGroups();
		$this->schemaValidationEnabled = $this->config['schemaUrl'] !== false && !in_array('no-doc', $groups);
	}


	/**
	 * @param string $method
	 * @param string $url
	 * @param array<string> $parameters
	 * @param array<string> $files
	 */
	protected function execute($method, $url, $parameters = [], $files = []) : void
	{
		if (!$this->schemaValidationEnabled) {
			// Schema validation is disabled
			parent::execute($method, $url, $parameters, $files);
			return;
		}

		$this->schema ??= $this->fetchSchema();
		$this->routerMap ??= $this->createRouterMap($this->schema);

		parent::execute($method, $url, $parameters, $files);

		$request = $this->getPsrRequest();
		$response = $this->getPsrResponse();

		$this->validateSchema($request, $response, $this->schema);
	}


	private function fetchSchema(): OpenApi
	{
		$schemaUrl = $this->config['schemaUrl'];
		if (!$schemaUrl) {
			throw new \RuntimeException("REST: schemaUrl configuration missing");
		}

		$this->connectionModule->client->request('GET', $schemaUrl);
		$schemaJson = $this->connectionModule->client->getInternalResponse()->getContent();

		$factory = new JsonFactory($schemaJson);
		return $factory->createSchema();
	}

	/**
	 * @return array<string, string>
	 */
	private function createRouterMap(OpenApi $schema): array
	{
		$map = [];
		$paths = $schema->paths ?: [];
		foreach ($paths as $mask => $path) {
			$map[(string)preg_replace('~\\{.+?\\}~i', '[^/]+', $mask)] = (string)$mask;
		}

		return $map;
	}

	/**
	 * @param array<string, string> $routerMap
	 */
	private function findRoute(string $path, array $routerMap): ?string
	{
		foreach ($routerMap as $patter => $mask) {
			if (preg_match("~^$patter\$~i", $path)) {
				return $mask;
			}
		}

		return null;
	}


	private function getPsrRequest(): ServerRequestInterface
	{
		$internalRequest = $this->getRunningClient()->getInternalRequest();
		$request = new ServerRequest(
			$internalRequest->getMethod(),
			$internalRequest->getUri(),
			$this->connectionModule->headers,
			$internalRequest->getContent()
		);
		return $request->withQueryParams(parse_query($request->getUri()->getQuery()));
	}

	private function getPsrResponse(): ResponseInterface
	{
		$internalResponse = $this->getRunningClient()->getInternalResponse();
		return new Response($internalResponse->getStatusCode(), $internalResponse->getHeaders(), $internalResponse->getContent());
	}


	private function validateSchema(ServerRequestInterface $request, ResponseInterface $response, OpenApi $schema): void
	{
		$path = $request->getUri()->getPath();
		$route = $this->findRoute($path, (array)$this->routerMap);
		if (!$route) {
			// there is no route for 404, it is correct so we skip validation
			if ($response->getStatusCode() !== 404) {
				$this->fail("No open-api specification found for url '$path'");
			}
			return;
		}

		$validator = new OpenApiSchemaValidator();
		$operationAddress = new OperationAddress($route, strtolower($request->getMethod()));

		if (200 <= $response->getStatusCode() && $response->getStatusCode() < 300) {
			$reqResult = $validator->validateRequest($schema, $operationAddress, $request);
			if (!$reqResult->isValid()) {
				$this->fail('Open-api request validation: ' . $reqResult->getErrorMessage());
			}
		}

		if ($response->getStatusCode() < 500) {
			$respResult = $validator->validateResponse($schema, $operationAddress, $response);
			if (!$respResult->isValid()) {
				$this->fail('Open-api response validation: ' . $respResult->getErrorMessage());
			}
		}
	}
}
