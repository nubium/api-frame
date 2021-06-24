<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi\Processor;

use OpenApi\Analysis;
use OpenApi\Annotations\MediaType;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Response;
use OpenApi\Annotations\Schema;
use Exception;
use const OpenApi\UNDEFINED;


/**
 * Add response 500 to every endpoint
 */
class ServerErrorResponseProcessor
{
	private const SERVER_ERROR_SCHEMA_NAME = 'ServerError';


	public function __invoke(Analysis $analysis): void
	{
		$allOperations = $analysis->getAnnotationsOfType(Operation::class);
		/** @var Schema[] $schemas */
		$schemas = $analysis->getAnnotationsOfType(Schema::class);

		$serverErrorSchema = $this->findServerErrorSchema($schemas);

		/** @var Operation $operation */
		foreach ($allOperations as $operation) {
			$this->addServerErrorResponse($operation, $serverErrorSchema);
		}
	}


	/**
	 * @param Schema[] $schemas
	 *
	 * @throws Exception
	 */
	private function findServerErrorSchema(array $schemas): Schema
	{
		$clientErrorSchemas = array_filter($schemas, function (Schema $schema) {
			return $schema->schema === self::SERVER_ERROR_SCHEMA_NAME;
		});

		if (empty($clientErrorSchemas)) {
			throw new Exception("There is no schema with name: " . self::SERVER_ERROR_SCHEMA_NAME);
		}

		return reset($clientErrorSchemas);
	}

	private function addServerErrorResponse(Operation $operation, Schema $serverErrorSchema): void
	{
		$mediaType = new MediaType(['mediaType' => 'application/json']);
		$mediaType->schema = $serverErrorSchema;

		$response = new Response([]);
		$response->response = '500';
		$response->description = 'Internal server error';
		$response->content = ['application/json' => $mediaType];

		if ($operation->responses == UNDEFINED) {
			$operation->responses = [$response];
		} else {
			$operation->responses[] = $response;
		}
	}
}
