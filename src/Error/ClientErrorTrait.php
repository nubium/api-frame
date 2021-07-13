<?php declare(strict_types=1);
namespace Nubium\ApiFrame\Error;

trait ClientErrorTrait
{
	protected static ?int $code;
	protected static ?string $message;
	protected static ?string $oASchemaName;
	protected static int $httpStatusCode;

	/** @var mixed[] */
	protected array $context = [];

	public static function getCode(): ?int
	{
		return static::$code;
	}

	public static function getMessage(): ?string
	{
		return static::$message;
	}

	public static function getOASchemaName(): ?string
	{
		return static::$oASchemaName ?? null;
	}

	public static function getHttpStatusCode(): int
	{
		return static::$httpStatusCode;
	}


	// dynamic methods, used to carry custom data for this specific error instance that might be helpful to api consumers - stuff like user ids, product identifiers etc.

	/** @return mixed[] */
	public function getContext(): array
	{
		return $this->context;
	}
}
