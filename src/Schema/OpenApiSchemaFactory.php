<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Schema;

use cebe\openapi\spec\OpenApi;
use League\OpenAPIValidation\PSR7\SchemaFactory\JsonFactory;

class OpenApiSchemaFactory
{
	public function createSchemaFromJson(string $json): OpenApi
	{
		$factory = new JsonFactory($json);
		return $factory->createSchema();
	}
}
