<?php declare(strict_types=1);
namespace Nubium\ApiFrame\Middleware;

use Nubium\ApiFrame\Error\ClientError;
use Nubium\ApiFrame\Http\ApiResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema()
 */
class RoutingError extends ClientError
{
	protected static ?int $code = 10000;
	protected static ?string $message = 'No matched route by given URL.';
	protected static int $httpStatusCode = ApiResponse::S404_NOT_FOUND;

	public function __construct(string $requestUri)
	{
		$this->context['url'] = $requestUri;
	}
}
