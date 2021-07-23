<?php declare(strict_types=1);
namespace Nubium\ApiFrame\Error;

interface IClientError
{
	// static methods, used by OpenAPI processors to generate OpenAPI documentation

	public static function getCode(): ?int;
	public static function getMessage(): ?string;
	/** Name of OpenAPI schema used as a blueprint for this client error. Default is CLASSNAME (used if NULL returned). */
	public static function getOASchemaName(): ?string;
	public static function getHttpStatusCode(): int;


	// dynamic methods, used to carry custom data for this specific error instance that might be helpful to api consumers - stuff like user ids, product identifiers etc.

	/** @return mixed[] */
	public function getContext(): array;
}
