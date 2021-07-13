<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi\Processor;

use Nubium\ApiFrame\Error\IClientError;
use OpenApi\Analysis;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\MediaType;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Response;
use OpenApi\Annotations\Schema;
use Exception;
use OpenApi\Generator;
use const OpenApi\UNDEFINED;


/**
 * Add #/components/schemas/ClientError to all 4xx error responses without own schema
 */
class ClientErrorResponseProcessor
{
	private Analysis $analysis;

	/** @var Schema[] */
	private array $schemasCache = [];

	public function __invoke(Analysis $analysis): void
	{
		$this->analysis = $analysis;
		$this->schemasCache = [];

		$allOperations = $analysis->getAnnotationsOfType(Operation::class);

		/** @var Schema[] $schemas */
		$schemas = $analysis->getAnnotationsOfType(Schema::class);

		/** @var Operation $operation */
		foreach ($allOperations as $operation) {
			if (is_iterable($operation->responses)) {
				// add error schema to all 4xx responses
				foreach ($operation->responses as $response) {
					$this->expandErrorSchemasForResponse($response);
				}
			}
		}
	}


	private function ensureJsonResponseContent(Response $operationResponse): MediaType
	{
		if ($operationResponse->content == UNDEFINED) {
			$operationResponse->content = [];
		}
		if (!isset($operationResponse->content['application/json'])) {
			$operationResponse->content['application/json'] = new MediaType([
				'mediaType' => 'application/json'
			]);
		}

		return $operationResponse->content['application/json'];
	}


	private function expandErrorSchemasForResponse(Response $response): void
	{
		// proceed only if some Xtra data are set
		if (!$response->x) {
			return;
		}

		$responseContent = $this->ensureJsonResponseContent($response);

		$exampleListSchema = new Schema([]);
		$exampleListSchema->anyOf = [];
		foreach ($response->x as $errorClassName) {
			// proceed only if Xtra data contain class describing a client error
			if (!is_a($errorClassName, IClientError::class, true)) {
				return;
			}

			$reflClass = new \ReflectionClass($errorClassName);

			// STEP 1: create and/or update components/schemas/* entry
			// if the schema was already created, move on
			$schemaName = $errorClassName::getOASchemaName() ?? $reflClass->getShortName();
			$schemaIndexName = $reflClass->getShortName();
			if (!isset($this->schemasCache[$schemaIndexName])) {
				$schema = $this->findSchemaByName($schemaName);
				// if we found the global schema, we can just alter it according to class IClientError interface
				// if we didn't, we have to add the global schema before altering it
				if (!$schema) {
					$schema = new Schema([]);
					$schema->schema = $schemaName;
					$schema->title = $schemaName;
					$this->analysis->addAnnotation($schema, null);

					//TODO this code actually doesn't work and I have no idea why, so we throw exception until fixed
					// @phpstan-ignore-next-line
					throw new Exception(sprintf("Found no schema by name `%s` (requested by error class `%s`).", $schemaName, $errorClassName));
				}
				if ($schema->properties == Generator::UNDEFINED) {
					$schema->properties = [];
				}
				// look for `code` property, if it doesnt exist then create one
				$codeProperty = $this->findPropertyInSchemaByName($schema, 'code');
				if (!$codeProperty) {
					$schema->properties[] = $codeProperty = new Property([]);
					$codeProperty->property ='code';
					$codeProperty->type = 'integer';
				}
				$codeProperty->example = $errorClassName::getCode();
				$codeProperty->description = "Constant: ".$errorClassName::getCode();

				// look for `message` property, if it doesnt exist then create one
				$messageProperty = $this->findPropertyInSchemaByName($schema, 'message');
				if (!$messageProperty) {
					$schema->properties[] = $messageProperty = new Property([]);
					$messageProperty->property ='message';
					$messageProperty->type = 'string';
				}
				if ($errorClassName::getMessage()) {
					/** @phpstan-ignore-next-line */
					$schema->description = $errorClassName::getMessage();
					$messageProperty->example = $errorClassName::getMessage();
				}

				// add schema to our cache
				$this->schemasCache[$schemaIndexName] = $schema;
			}

			// STEP 2: add the entry to response
			$schema = $this->schemasCache[$schemaIndexName];
			$subSchema = unserialize(serialize($schema));
			$subSchema->title = $schemaName;
			$subSchema->ref = '#/components/schemas/'.$schemaName;
			$exampleListSchema->anyOf[] = $subSchema;
		}

		$responseContent->schema = $exampleListSchema;
	}

	private function findPropertyInSchemaByName(Schema $schema, string $propertyName): ?Property
	{
		/** @phpstan-ignore-next-line */
		if ($schema->properties === Generator::UNDEFINED) {
			return null;
		}
		foreach ($schema->properties as $property) {
			if ($property->property === $propertyName) {
				return $property;
			}
		}
		return null;
	}

	private function findSchemaByName(string $schemaName): ?Schema
	{
		/** @var Schema[] $schemas */
		$schemas = $this->analysis->getAnnotationsOfType(Schema::class);

		$foundSchemas = array_filter($schemas, fn(Schema $schema) => ($schema->schema === $schemaName));

		if (empty($foundSchemas)) {
			return null;
		}

		return reset($foundSchemas);
	}
}
