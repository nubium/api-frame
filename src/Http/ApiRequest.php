<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

use Apitte\Core\Http\RequestAttributes;
use Apitte\Core\Schema\Endpoint;

class ApiRequest extends \Apitte\Core\Http\ApiRequest
{
	public function getEndpoint(): Endpoint
	{
		$endpoint = $this->getAttribute(RequestAttributes::ATTR_ENDPOINT);
		if (!$endpoint instanceof Endpoint) {
			throw new \RuntimeException(Endpoint::class . ' expected to be set in endpoint attribute of request. Router should do it.');
		}

		return $endpoint;
	}
}
