<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Loader;

use Apitte\Core\DI\Loader\ILoader;
use Apitte\Core\Schema\SchemaBuilder;
use Apitte\Core\UI\Controller\IController;
use Doctrine\Common\Annotations\Reader;
use ReflectionClass;

/**
 * This is pretty much 1:1 copy of DoctrineAnnotationLoader, but can't reuse the code because of strange inheritance model and final class.
 */
class ControllerLoader implements ILoader
{
	use ControllerLoaderTrait;

	/** @var IController[] */
	private array $controllers;

	/**
	 * @param IController[] $controllers
	 */
	public function __construct(array $controllers, Reader $reader)
	{
		$this->controllers = $controllers;
		$this->reader = $reader;
	}

	public function load(SchemaBuilder $builder): SchemaBuilder
	{
		// Iterate over all controllers
		foreach ($this->controllers as $controller) {
			// Analyse all parent classes
			$class = $this->analyseClass(get_class($controller));

			// Check if a controller or his abstract implements IController,
			// otherwise, skip this controller
			if (!$this->acceptController($class)) {
				continue;
			}
			/** @var ReflectionClass<IController> $class */

			// Create scheme endpoint
			$schemeController = $builder->addController(get_class($controller));

			// Parse @Path, @ControllerId
			$this->parseControllerClassAnnotations($schemeController, $class);

			// Parse @Method, @Path
			$this->parseControllerMethodsAnnotations($schemeController, $class);
		}

		return $builder;
	}
}
