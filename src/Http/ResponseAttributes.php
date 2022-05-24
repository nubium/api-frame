<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Http;

interface ResponseAttributes extends \Apitte\Core\Http\ResponseAttributes
{
	public const ATTR_REQUEST = 'api-frame.request';
}
