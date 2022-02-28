<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi\Processor;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;


/**
 * Adds headers that can be used on every endpoint
 */
class TransparentHeadersProcessor
{
	public function __invoke(Analysis $analysis): void
	{
		$allOperations = $analysis->getAnnotationsOfType(Operation::class);

		/** @var Operation $operation */
		foreach ($allOperations as $operation) {
			$this->addTransparentHeaders($operation);
		}
	}

	private function addTransparentHeaders(Operation $operation): void
	{
		if ($operation->parameters == Generator::UNDEFINED) {
			$operation->parameters = [];
		}

		$schema = new Schema([]);
		$schema->type = 'integer';
		$schema->minimum = 0;

		$header = new Parameter([]);
		$header->in = 'header';
		$header->name = 'X-Retry-Count';
		$header->description = 'Count of already sent tries. First request should has 0 and so on.';
		$header->schema = $schema;

		$operation->parameters[] = $header;
	}
}
