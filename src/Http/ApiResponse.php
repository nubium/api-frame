<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

use Apitte\Negotiation\Http\ArrayEntity;

class ApiResponse extends \Apitte\Core\Http\ApiResponse
{
	/**
	 * @param array<mixed> $data
	 */
	public function withArrayEntity(array $data): self
	{
		return $this->withEntity(ArrayEntity::from($data));
	}
}
