<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Edge;

use Shared\NetteDiScope\ScopeExtension;

class ApiFrameScopeExtension extends ScopeExtension
{
	protected function getConfigFilePaths(): array
	{
		return [__DIR__ . '/config.neon'];
	}
}
