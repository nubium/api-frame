<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi\Processor;

use OpenApi\Analysis;
use OpenApi\Annotations\Operation;
use const OpenApi\UNDEFINED;

/**
 * Generate the OperationId compatible with ReDoc (avoid back slash)
 */
class CustomOperationIdProcessor
{
	public function __invoke(Analysis $analysis): void
	{
		$allOperations = $analysis->getAnnotationsOfType(Operation::class);

		foreach ($allOperations as $operation) {
			/** @phpstan-ignore-next-line */
			if ($operation->operationId !== UNDEFINED) {
				continue;
			}
			$context = $operation->_context;

			/** @phpstan-ignore-next-line */
			if ($context && $context->method) {
					if ($context->class) {
						if ($context->namespace) {
									$operation->operationId = $context->namespace . "/" . $context->class . "::" . $context->method;
								} else {
									$operation->operationId = $context->class . "::" . $context->method;
								}
				} else {
							$operation->operationId = $context->method;
						}
				$operation->operationId = str_replace("\\", "/", $operation->operationId);
			}
		}
	}
}
