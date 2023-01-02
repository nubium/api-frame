<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Bridge\Apitte;

use Apitte\Core\DI\Plugin\CoreServicesPlugin;
use Nette\DI\Config;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class ApiExtension extends \Apitte\Core\DI\ApiExtension
{
	public function getConfigSchema(): Schema
	{
		$parameters = $this->getContainerBuilder()->parameters;
		return Expect::structure([
			'catchException' => Expect::bool(true),
			'debug' => Expect::bool($parameters['debugMode'] ?? false),
			'plugins' => Expect::array()->default([
				CoreServicesPlugin::class => [],
				CoreSchemaPlugin::class => [],
			]),
		]);
	}
}