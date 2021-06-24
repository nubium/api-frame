<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Schema\OpenApi;

use Exception;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use Throwable;

class OpenApiSchemaValidationResult
{
	/** @var Throwable|null */
	private $error;


	public function __construct(?Throwable $error)
	{
		$this->error = $error;
	}


	public function isValid(): bool
	{
		return $this->error === null;
	}

	public function getError(): ?Throwable
	{
		return $this->error;
	}

	public function getErrorMessage(): ?string
	{
		if ($this->error === null) {
			return null;
		} else if ($this->error instanceof ValidationFailed) {
			$message = "OpenAPI schema validation failed: " . $this->error->getMessage();
			$previous = $this->error->getPrevious();

			if ($previous instanceof Exception) {
				$message .= ' // ' . $previous->getMessage();
			}

			return $message;
		} else {
			return "OpenAPI schema validation died: " . $this->error->getMessage();
		}
	}
}
