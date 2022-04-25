<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi;

use cebe\openapi\spec\OpenApi;
use ErrorException;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @todo validate OpenAPI schema itself before creating validator or find more robust validator
 */
class OpenApiSchemaValidator
{
	/** @var ValidatorBuilder */
	private $validatorBuilder;


	public function __construct()
	{
		$this->validatorBuilder = new ValidatorBuilder();
	}


	public function validateRequest(OpenApi $schema, OperationAddress $operationAddress, ServerRequestInterface $request): OpenApiSchemaValidationResult
	{
		$error = $this->safeCall(function () use ($schema, $operationAddress, $request) {
			$validator = $this->validatorBuilder->fromSchema($schema)->getRoutedRequestValidator();
			$validator->validate($operationAddress, $request);
		});

		return new OpenApiSchemaValidationResult($error);
	}

	public function validateResponse(OpenApi $schema, OperationAddress $operationAddress, ResponseInterface $response): OpenApiSchemaValidationResult
	{
		$error = $this->safeCall(function () use ($schema, $operationAddress, $response) {
			$validator = $this->validatorBuilder->fromSchema($schema)->getResponseValidator();
			$validator->validate($operationAddress, $response);
		});

		return new OpenApiSchemaValidationResult($error);
	}


	private function safeCall(callable $callback): ?Throwable
	{
		try {
			set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext = []) {
				if (error_reporting() != 0) {
					throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
				}
				return true;
			});
			$callback();
		} catch (Throwable $error) {
			return $error;
		} finally {
			restore_error_handler();
		}

		return null;
	}
}
