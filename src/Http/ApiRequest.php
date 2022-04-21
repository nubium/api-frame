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

	public function getRemoteAddr(): string
	{
		/** @var string $ip */
		$ip = $this->getServerParams()['REMOTE_ADDR'] ?? throw new \InvalidArgumentException('Request\'s server params do not contain REMOTE_ADDR, probably problem with webserver configuration.');

		return $ip;
	}
}
