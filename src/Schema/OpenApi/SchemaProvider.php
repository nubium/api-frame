<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi;

use cebe\openapi\spec\OpenApi as ValidatorOpenAPi;
use League\OpenAPIValidation\PSR7\SchemaFactory\JsonFactory;
use OpenApi\Annotations\OpenApi as SwaggerOpenApi;
use OpenApi\Generator;
use OpenApi\Processors\OperationId;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use function OpenApi\scan;


class SchemaProvider
{
	private ?SwaggerOpenApi $openApi = null;
	private ?ValidatorOpenApi $validatorOpenApi = null;

	/**
	 * @param string|string[] $projectRoot
	 * @param callable[] $additionalProcessors
	 * @deprecated
	 */
	public function getDescribingSchemaFromStaticAnalysis(mixed $projectRoot, ?callable $operationIdProcessorReplacement = null, array $additionalProcessors = []): SwaggerOpenApi
	{
		return $this->getDescriptiveSchemaFromStaticAnalysis(null, $projectRoot, $operationIdProcessorReplacement, $additionalProcessors);
	}

	/**
	 * @param string|string[] $projectRoot
	 * @param callable[] $additionalProcessors
	 */
	public function getDescriptiveSchemaFromStaticAnalysis(
		?LoggerInterface $logger,
		mixed $projectRoot,
		?callable $operationIdProcessorReplacement = null,
		array $additionalProcessors = [],
	): SwaggerOpenApi {
		if ($this->openApi) {
			return $this->openApi;
		}

		$projectRoot = (array)$projectRoot;
		$what = Finder::create()
			->files()
			->name('*.php')
			->in(array_values($projectRoot));

		$generator = new Generator($logger);
		$processors = $generator->getProcessors();

		if ($operationIdProcessorReplacement) {
			foreach ($processors as $key => $processor) {
				if ($processor instanceof OperationId) {
					$processors[$key] = $operationIdProcessorReplacement;
					break;
				}
			}
		}

		$processors = [...$processors, ...$additionalProcessors];
		$generator->setProcessors($processors);
		$generator->generate($what);

		return $this->openApi = scan($what, ['processors' => $processors]);
	}

	public function getValidatorSchemaFromStaticAnalysis(SwaggerOpenApi $swaggerSchema): ValidatorOpenAPi
	{
		if ($this->validatorOpenApi === null) {
			$this->validatorOpenApi = $this->createValidatorSchemaFromJson($swaggerSchema->toJson());
		}

		return $this->validatorOpenApi;
	}

	private function createValidatorSchemaFromJson(string $json): ValidatorOpenAPi
	{
		$factory = new JsonFactory($json);
		return $factory->createSchema();
	}
}
