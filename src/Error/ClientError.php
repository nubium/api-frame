<?php declare(strict_types=1);
namespace Nubium\ApiFrame\Error;

abstract class ClientError implements IClientError
{
	use ClientErrorTrait;

	// duplicated for better visibility
	protected static ?int $code;
	protected static ?string $message;
	protected static ?string $oASchemaName;
	protected static int $httpStatusCode;
}
