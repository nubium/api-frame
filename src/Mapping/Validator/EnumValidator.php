<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Mapping\Validator;

use Nubium\ApiFrame\Mapping\Annotation\Validator\AssertEnum;
use SpareParts\Enum\Converter\LowercaseConverter;
use SpareParts\Enum\Exception\InvalidEnumValueException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EnumValidator extends ConstraintValidator
{
	public function validate(mixed $value, Constraint $constraint): void
	{
		if (!$constraint instanceof AssertEnum) {
			throw new UnexpectedTypeException($constraint, AssertEnum::class);
		}

		// let other validators handle nullability
		if ($value === null) {
			return;
		}

		// only string conversion is supported by enum converters
		if (!is_string($value)) {
			throw new UnexpectedValueException($value, 'string');
		}

		// convert to enum or fail...
		try {
			(new LowercaseConverter($constraint->enum))->toEnum($value);
		} catch (InvalidEnumValueException $e) {
			$this->context->buildViolation($constraint->message)
				->setParameter('{{ string }}', $value)
				->addViolation();
		}
	}
}
