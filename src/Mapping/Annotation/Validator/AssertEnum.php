<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Mapping\Annotation\Validator;

use Nubium\ApiFrame\Mapping\Validator\EnumValidator;
use SpareParts\Enum\Enum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * @Annotation
 */
class AssertEnum extends Constraint
{
	/** @var string */
	public $enum;

	/** @var string */
	public $message;

	public function __construct($options = null)
	{
		parent::__construct($options);

		if (!class_exists($this->enum) || !is_a($this->enum, Enum::class, true)) {
			throw new InvalidArgumentException(sprintf(
				'The "enum" must be valid class extending %s ("%s" given).',
				Enum::class,
				$this->enum
			));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOption()
	{
		return 'enum';
	}

	public function validatedBy()
	{
		return EnumValidator::class;
	}
}
