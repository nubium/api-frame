<?php declare(strict_types=1);
namespace Nubium\ApiFrame\Bridge\Apitte;

use Apitte\Core\DI\Loader\NeonLoader;
use Apitte\Core\Schema\SchemaBuilder;
use Nette\DI\Config\Adapters\NeonAdapter;
use Nette\Utils\Arrays;

class CoreSchemaPlugin extends \Apitte\Core\DI\Plugin\CoreSchemaPlugin
{

	protected function loadSchema(SchemaBuilder $builder): SchemaBuilder
	{
		$loaders = $this->config->loaders;

		//TODO - resolve limitation - Controller defined by one of loaders cannot be modified by other loaders

		if ($loaders->annotations->enable) {
			$loader = new DoctrineAnnotationLoader($this->getContainerBuilder());
			$builder = $loader->load($builder);
		}

		if ($loaders->neon->enable) {
			$files = $loaders->neon->files;

			// Load schema from files
			$adapter = new NeonAdapter();
			$schema = [];
			foreach ($files as $file) {
				$schema = Arrays::mergeTree($schema, $adapter->load($file));
			}

			$loader = new NeonLoader($schema);
			$builder = $loader->load($builder);
		}

		return $builder;
	}

}
