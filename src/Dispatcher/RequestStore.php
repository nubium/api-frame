<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Dispatcher;

use Psr\Http\Message\ServerRequestInterface;

class RequestStore
{
	private ServerRequestInterface $request;


	public function setRequest(ServerRequestInterface $request): void
	{
		$this->request = $request;
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}
}
