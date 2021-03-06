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
			->writeBody(self::jsonEncode($entity->getData()));
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
		$data = self::errorToResponseData($error, $isDebug);
		return self::jsonEncode($data);
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
	 * @param mixed $data
	 * @throws \JsonException
	 */
	private static function jsonEncode($data): string
	{
		return json_encode($data, JSON_THROW_ON_ERROR);
	}
}
