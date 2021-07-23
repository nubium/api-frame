<?php declare(strict_types=1);

namespace Nubium\ApiFrame\Tests\Edge\Mapping;

use PHPUnit\Framework\TestCase;
use Nubium\ApiFrame\Mapping\EntityDeclarationException;
use Nubium\ApiFrame\Mapping\RequestEntity;

class RequestEntityTest extends TestCase
{
	/**
	 * @testWith
	 * [null, null]
	 * [2020, null]
	 * ["foobar", null]
	 * ["2020-02-20 10:20:30", null]
	 * ["2020-44-20T10:20:30Z", null]
	 * ["2020-02-20T10:20:30Z", "2020-02-20T10:20:30.000Z"]
	 * ["2020-02-20T10:20:30.123Z", "2020-02-20T10:20:30.123Z"]
	 * ["2020-02-20T10:20:30+00:00", "2020-02-20T10:20:30.000Z"]
	 * ["2020-02-20T09:20:30-01:00", "2020-02-20T10:20:30.000Z"]
	 * ["2020-02-20T11:20:30.123+01:00", "2020-02-20T10:20:30.123Z"]
	 *
	 * @param mixed $inputDate
	 * @param string|null $expectedDate
	 */
	public function testParseDateTime($inputDate, ?string $expectedDate = null): void
	{
		$entityFactory = new class() extends RequestEntity {
			/** @var \DateTimeImmutable|null */
			public $date;
		};

		$entityInstance = $entityFactory->factory(['date' => $inputDate]);

		if ($expectedDate !== null) {
			// valid date
			$this->assertInstanceOf(\DateTimeImmutable::class, $entityInstance->date);
			$this->assertEquals(
				\DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC3339_EXTENDED, $expectedDate),
				$entityInstance->date
			);
			$this->assertEquals(
				date_default_timezone_get(),
				$entityInstance->date->getTimezone()->getName(),
				"Date should be converted to current time zone"
			);
		} else {
			// invalid date
			$this->assertSame($inputDate, $entityInstance->date);
		}
	}

	public function testDateTimeValidAnnotations(): void
	{
		$entityFactory = new class() extends RequestEntity {
			/** @var \DateTimeImmutable|null */
			public $date1;
			/** @var \DateTimeInterface|null */
			public $date2;
			/** @var \DateTimeImmutable */
			public $date3;
			/** @var \DateTimeInterface */
			public $date4;
		};

		$entityInstance = $entityFactory->factory([
			'date1' => '2020-02-20T10:20:30Z',
			'date2' => '2020-02-20T10:20:30Z',
			'date3' => '2020-02-20T10:20:30Z',
			'date4' => '2020-02-20T10:20:30Z',
		]);

		$this->assertInstanceOf(\DateTimeImmutable::class, $entityInstance->date1);
		$this->assertInstanceOf(\DateTimeImmutable::class, $entityInstance->date2);
		$this->assertInstanceOf(\DateTimeImmutable::class, $entityInstance->date3);
		$this->assertInstanceOf(\DateTimeImmutable::class, $entityInstance->date4);
	}

	public function testDateTimeInvalidAnnotations(): void
	{
		$entityFactory = new class() extends RequestEntity {
			/** @var \DateTime */
			public $date;
		};

		$this->expectException(EntityDeclarationException::class);
		$this->expectExceptionMessage('Unable to read @var declaration');
		$entityFactory->factory(['date' => '2020-02-20T10:20:30Z']);
	}
}
