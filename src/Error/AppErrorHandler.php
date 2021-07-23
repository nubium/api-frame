<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Error;

use Apitte\Core\Dispatcher\DispatchError;
use Apitte\Core\ErrorHandler\SimpleErrorHandler;
use Apitte\Core\Exception\Runtime\SnapshotException;
use Apitte\Core\Http\ApiResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Nubium\ApiFrame\Http\IApiResponseFactory;
use Nubium\ApiFrame\Http\IResponseTransformer;
use Throwable;

class AppErrorHandler extends SimpleErrorHandler
{
	private LoggerInterface $logger;
	private IResponseTransformer $transformer;
	private IApiResponseFactory $apiResponseFactory;


	public function __construct(LoggerInterface $logger, IResponseTransformer $transformer, IApiResponseFactory $apiResponseFactory)
	{
		$this->logger = $logger;
		$this->transformer = $transformer;
		$this->apiResponseFactory = $apiResponseFactory;
	}


	/**
	 * @throws Throwable
	 */
	public function handle(DispatchError $error): ApiResponse
	{
		$this->logError($error->getError());

		return parent::handle($error);
	}


	protected function logError(Throwable $error): void
	{
		if ($error instanceof SnapshotException) {
			$error = $error->getPrevious();
		}

		$this->logger->log(LogLevel::ERROR, $error->getMessage(), ['exception' => $error]);
	}

	protected function createResponseFromError(Throwable $error): ApiResponse
	{
		$code = $error->getCode() >= 600 || $error->getCode() < 500 ? 500 : $error->getCode();
		$response = $this->apiResponseFactory->createApiResponse()
			->withStatus($code);

		return $this->transformer->transformError($response, $error);
	}
}
