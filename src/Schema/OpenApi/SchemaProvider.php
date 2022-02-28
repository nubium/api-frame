<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi;

use cebe\openapi\spec\OpenApi as ValidatorOpenAPi;
use League\OpenAPIValidation\PSR7\SchemaFactory\JsonFactory;
use OpenApi\Annotations\OpenApi as SwaggerOpenApi;
use OpenApi\Generator;
use OpenApi\Processors\OperationId;
use Symfony\Component\Finder\Finder;


class SchemaProvider
{
	private ?SwaggerOpenApi $openApi = null;
	private ?ValidatorOpenApi $validatorOpenApi = null;

	/**
	 * @param callable[] $additionalProcessors
	 */
	public function getDescribingSchemaFromStaticAnalysis(string $projectRoot, ?callable $operationIdProcessorReplacement = null, array $additionalProcessors = []): SwaggerOpenApi
	{
		if ($this->openApi) {
			return $this->openApi;
		}

		$what = Finder::create()
			->files()
			->name('*.php')
			->in([$projectRoot, __DIR__.'/../..']);

		$generator = new Generator();
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
		$this->openApi = $generator->generate($what);
		if ($this->openApi === null) {
			throw new \Exception('Invalid openApi schema');
		}
		return $this->openApi;
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
