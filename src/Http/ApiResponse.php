<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

use Apitte\Negotiation\Http\ArrayEntity;
use Nubium\ApiFrame\Error\IClientError;
use OpenApi\Annotations as OA;

class ApiResponse extends \Apitte\Core\Http\ApiResponse
{
	/**
	 * @param array<mixed> $data
	 */
	public function withArrayEntity(array $data): self
	{
		return $this->withEntity(ArrayEntity::from($data));
	}

	/**
	 * @OA\Schema(
	 *     schema="ClientError",
	 *     required={"code", "message"},
	 *     @OA\Property(
	 *         property="code",
	 *         type="integer",
	 *         example=10000
	 *     ),
	 *     @OA\Property(
	 *         property="message",
	 *         description="Describes error. Doesn't have to be constant. Use code to decide programmatically.",
	 *         type="string",
	 *         example="Bad request."
	 *     ),
	 *     @OA\Property(
	 *         property="context",
	 *         description="Provides more detailed information about error. Schema can be variable for different errors.",
	 *         type="object",
	 *         example={"given_data":"You have some error in given data."}
	 *     )
	 * )
	 */
	public function withError(IClientError $error): self
	{
		$data = [
			'code' => $error::getCode(),
			'message' => $error::getMessage(),
		];
		if ($error->getContext()) {
			$data['context'] = $error->getContext();
		}

		return $this
			->withArrayEntity($data)
			->withStatus($error::getHttpStatusCode());
	}
}
