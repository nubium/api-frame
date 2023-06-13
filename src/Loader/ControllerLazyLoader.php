<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Loader;

use Apitte\Core\Annotation\Controller\Id;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Negotiations;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestBody;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\Responses;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\DI\Loader\ILoader;
use Apitte\Core\Schema\Builder\Controller\Controller;
use Apitte\Core\Schema\EndpointRequestBody;
use Apitte\Core\Schema\SchemaBuilder;
use Apitte\Core\UI\Controller\IController;
use Doctrine\Common\Annotations\Reader;
use Nette\Neon\Neon;
use ReflectionClass;
use ReflectionMethod;

/**
 * This is pretty much 1:1 copy of DoctrineAnnotationLoader, but can't reuse the code because of strange inheritance model and final class.
 */
class ControllerLazyLoader implements ILoader
{
	use ControllerLoaderTrait;

	/**
	 * @param array<class-string<IController>> $controllerClassnameList
	 */
	public function __construct(private array $controllerClassnameList, Reader $reader)
	{
		$this->reader = $reader;
	}

	public function load(SchemaBuilder $builder): SchemaBuilder
	{
		// Iterate over all controllers
		foreach ($this->controllerClassnameList as $controllerClassname) {
			// Analyse all parent classes
			$class = $this->analyseClass($controllerClassname);

			// Check if a controller or his abstract implements IController,
			// otherwise, skip this controller
			if (!$this->acceptController($class)) {
				continue;
			}
			/** @var ReflectionClass<IController> $class */

			// Create scheme endpoint
			$schemeController = $builder->addController($controllerClassname);

			// Parse @Path, @ControllerId
			$this->parseControllerClassAnnotations($schemeController, $class);

			// Parse @Method, @Path
			$this->parseControllerMethodsAnnotations($schemeController, $class);
		}

		return $builder;
	}
}
