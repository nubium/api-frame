<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http\ResponseTransformer;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Negotiation\Http\AbstractEntity;
use OpenApi\Annotations as OA;
use Nubium\ApiFrame\Http\ApiResponse;
use Nubium\ApiFrame\Http\IResponseTransformer;
use Throwable;

class JsonTransformer implements IResponseTransformer
{
	private const CONTENT_TYPE = 'application/json';


	private bool $isDebug;


	public function __construct(bool $isDebug)
	{
		$this->isDebug = $isDebug;
	}


	public function transformResponse(ApiResponse $response): ApiResponse
	{
		$isJsonContentType = $response->getHeader('Content-Type') === [self::CONTENT_TYPE];

		if ($response->hasHeader('Content-Type') && !$isJsonContentType) {
			throw new \RuntimeException("Response already has 'Content-Type' header. So it cannot be transformed to JSON.");
		}

		if ($response->getBody()->getSize() > 0) {
			if (!$isJsonContentType) {
				throw new \RuntimeException("Response already contains body. So it cannot be transformed to JSON.");
			}
		} else if ($entity = $response->getEntity()) {
			$response = $this->transformEntity($response, $entity);
		}

		return $response->withHeader('Content-Type', self::CONTENT_TYPE);
	}

	public function transformError(ApiResponse $response, Throwable $error): ApiResponse
	{
		$body = static::errorToResponse($error, $this->isDebug);

		return $response
			->withHeader('Content-Type', self::CONTENT_TYPE)
			->writeBody($body);
	}


	private function transformEntity(ApiResponse $response, AbstractEntity $entity): ApiResponse
	{
		return $response
			->writeBody(static::jsonEncode($entity->getData(), $this->isDebug));
	}


	/**
	 * @OA\Schema(
	 *     schema="ServerError",
	 *     required={"exception"},
	 *     @OA\Property(
	 *         property="exception",
	 *         type="string",
	 *         example="Application encountered an internal error. Please try again later."
	 *     )
	 * )
	 */
	public static function errorToResponse(?Throwable $error, bool $isDebug): string
	{
		$data = static::errorToResponseData($error, $isDebug);
		return static::jsonEncode($data, $isDebug);
	}

	/** @return array<string, mixed> */
	private static function errorToResponseData(?Throwable $error, bool $isDebug): array
	{
		$data = [];

		if ($error instanceof ClientErrorException || $error instanceof ServerErrorException) {
			$data['exception'] = $error->getMessage();
			if ($error->getContext() !== null) {
				$data['context'] = $error->getContext();
			}
		} else {
			$data['exception'] = $isDebug && $error ? $error->getMessage() : 'Application encountered an internal error. Please try again later.';
		}

		if ($isDebug) {
			$data['stacktrace'] = $error ? explode("\n", $error->getTraceAsString()) : '?';
		}

		return $data;
	}

	/**
	 * @throws \JsonException
	 */
	private static function jsonEncode(mixed $data, bool $isDebug): string
	{
		$encodedData = json_encode($data, ($isDebug ? JSON_PRETTY_PRINT : 0) | JSON_THROW_ON_ERROR);
		if ($isDebug) {
			$encodedData .= "\n";
		}

		return $encodedData;
	}
}
