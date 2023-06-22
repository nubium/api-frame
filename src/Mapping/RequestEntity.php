<?php
declare(strict_types=1);

namespace Nubium\ApiFrame\Mapping;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Mapping\Request\BasicEntity;
use Apitte\Core\Mapping\Request\IRequestEntity;
use Apitte\Core\Schema\Endpoint;
use Nette\Utils\Strings;

class RequestEntity extends BasicEntity
{
	/** @var bool */
	private $_isFromBody = false;

	/** @var bool[] */
	private $_presentProperties = [];

	/** @var array<string,string> propertyName=>phpDoc */
	private $_propertyDocs = [];

	/** @var string[] */
	protected $_propertyTypes = [];


	public function isFromBody(): bool
	{
		return $this->_isFromBody;
	}


	public function isPresent(string $propertyName): bool
	{
		if (!array_key_exists($propertyName, $this->_presentProperties)) {
			throw new \InvalidArgumentException('Unknown property "' . $propertyName . '"');
		}

		return $this->_presentProperties[$propertyName];
	}

	/**
	 * @param string[] $propertyNames
	 * @return bool
	 */
	public function isAnyPresent(array $propertyNames): bool
	{
		$diff = array_diff($propertyNames, array_keys($this->_presentProperties));
		if (count($diff) > 0) {
			throw new \InvalidArgumentException('Unknown properties "' . join(', ', $diff) . '"');
		}

		foreach ($propertyNames as $propertyName) {
			if ($this->isPresent($propertyName)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * @return BasicEntity|null
	 */
	public function fromRequest(ApiRequest $request): ?IRequestEntity
	{
		$inst = parent::fromRequest($request);
		if ($inst === null && $request->getMethod() === Endpoint::METHOD_DELETE) {
			$inst = $this->fromBodyRequest($request);
		}
		return $inst;
	}


	protected function fromBodyRequest(ApiRequest $request): BasicEntity
	{
		$inst = parent::fromBodyRequest($request);
		$inst->_isFromBody = true;
		return $inst;
	}


	protected function fromGetRequest(ApiRequest $request): BasicEntity
	{
		$inst = parent::fromGetRequest($request);
		$inst->_isFromBody = false;
		return $inst;
	}


	public function factory(array $data): BasicEntity
	{
		/** @var static $inst */
		$inst = parent::factory($data);

		/** @var array<array{'name': string, 'type': mixed, 'defaultValue': mixed}> $properties */
		$properties = $inst->getRequestProperties();
		foreach ($properties as $property) {
			$inst->_presentProperties[$property['name']] = array_key_exists(strval($property['name']), $data);
		}

		return $inst;
	}


	protected function normalize(string $property, $value)
	{
		if ($value === null) {
			return $value;
		}
		switch ($this->getPropertyType($property)) {
			case 'int':
			case 'integer':
				return $this->normalizeInteger($value);
			case 'float':
			case 'double':
				return $this->normalizeFloat($value);
			case 'bool':
			case 'boolean':
				return $this->normalizeBoolean($value);
			case 'string':
				return $this->normalizeString($value);
			case 'DateTimeImmutable':
			case '\DateTimeImmutable':
			case 'DateTimeInterface':
			case '\DateTimeInterface':
				return $this->normalizeDateTimeImmutable($value);
			case 'string[]':
				return $this->normalizeStringArray($value);
			case 'mixed': // PNJ
			default:
				return $value;
		}
	}


	protected function getPropertyType(string $property): string
	{
		if (!isset($this->_propertyTypes[$property])) {
			$match = Strings::match(
				$this->getPropertyDoc($property),
				'/@var\s+(null\|)?(\?)?(?<type>mixed|string|int|integer|bool|boolean|float|double|string\[\]|array\[\]|\\\\?DateTimeImmutable|\\\\?DateTimeInterface)(\|null)?(\s|$)/'
			);
			if ($match === null) {
				throw new EntityDeclarationException(
					sprintf('Unable to read @var declaration for property "%s::%s".', get_class($this), $property)
				);
			}
			$this->_propertyTypes[$property] = $match['type'];
		}

		return $this->_propertyTypes[$property];
	}


	protected function getPropertyDoc(string $property): string
	{
		if (!isset($this->_propertyDocs[$property])) {
			$rp = new \ReflectionProperty(get_class($this), $property);
			$this->_propertyDocs[$property] = $rp->getDocComment() ?: '';
		}

		return $this->_propertyDocs[$property];
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function normalizeString($value)
	{
		if (is_scalar($value)) {
			return (string)$value;
		}
		return $value;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function normalizeFloat($value)
	{
		if (is_float($value) || is_int($value)) {
			return (float)$value;
		}
		if (is_string($value) && Strings::match($value, '/^[+-]?[\d]+(\.[\d]+)?$/')) {
			return (float)$value;
		}
		return $value;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function normalizeInteger($value)
	{
		if (is_int($value)) {
			return $value;
		}
		if (is_float($value) && (float)(int)$value === $value) {
			return (int)$value;
		}
		if (is_string($value) && Strings::match($value, '/^[+-]?[\d]+$/')) {
			return (int)$value;
		}
		return $value;
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function normalizeBoolean($value)
	{
		if (is_bool($value)) {
			return $value;
		}
		if ($value === 1 || $value === '1' || $value === 'true') {
			return true;
		}
		if ($value === 0 || $value === '0' || $value === 'false') {
			return false;
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function normalizeDateTimeImmutable($value)
	{
		$acceptedFormats = [
			\DateTimeInterface::RFC3339,
			// ".v" is buggy from 7.1 to 7.3 @see https://bugs.php.net/bug.php?id=75577
			str_replace('.v', '.u', \DateTimeInterface::RFC3339_EXTENDED),
		];
		foreach ($acceptedFormats as $format) {
			$dateTime = \DateTimeImmutable::createFromFormat($format, is_scalar($value) ? (string)$value : '');
			$errors = \DateTimeImmutable::getLastErrors();
			if ($dateTime instanceof \DateTimeImmutable
				&& $errors
				&& $errors['warning_count'] == 0 && $errors['error_count'] == 0) {
				return $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			}
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function normalizeStringArray($value)
	{
		if (is_array($value)) {
			return array_map([$this, 'normalizeString'], $value);
		}
		return $value;
	}
}
