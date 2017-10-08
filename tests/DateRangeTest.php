<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.0
 * @license MIT
 */

namespace jasonjgardner\DateRange\Test;

use DateTime,
	DateTimeZone,
	DateInterval,
	PHPUnit\Framework\TestCase,
	jasonjgardner\DateRange\DateRange;

/**
 * DateRange test suite
 * @author Jason Gardner
 * @package jasonjgardner\DateRange\Test
 * @coversDefaultClass \jasonjgardner\DateRange\DateRange
 */
class DateRangeTest extends TestCase
{
	/**
	 * Tests if the class constructor can accept a variety of date variable types
	 * @covers ::__construct(
	 * @covers ::toDate()
	 * @covers ::getBoundaries()
	 * @covers ::setInterval()
	 * @covers ::instantiate()
	 * @covers ::getStartDate()
	 * @covers ::getEndDate()
	 * @dataProvider provideAcceptArguments
	 * @group constructor
	 * @param string|int|\DateTime      $start         Start date
	 * @param string|int|\DateTime|null $end           End date
	 * @param string|null|\DateTimeZone $timezone      Timezone of date range dates
	 * @param string|null|\DateInterval $interval      Interval used when iterating over dates
	 * @param \DateTime                 $expectedStart Expected start DateTime
	 * @param \DateTime                 $expectedEnd   Expected end DateTime
	 */
	public function testAcceptArguments($start, $end, $timezone, $interval, DateTime $expectedStart, DateTime $expectedEnd): void
	{
		$DateRange = new DateRange($start, $end, $timezone, $interval);

		$this->assertEquals(
			$expectedStart,
			$DateRange->getStartDate(),
			'Start dates are not the same time'
		);

		$this->assertEquals(
			$expectedEnd,
			$DateRange->getEndDate(),
			'End dates are not the same time'
		);
	}

	/**
	 * @return array Array of mixed items which are acceptable arguments in `DateTime::__construct()`
	 */
	public function provideAcceptArguments(): array
	{
		$timezone = new DateTimeZone('America/Chicago');
		$utc = new DateTimeZone('UTC');

		$startStr = '2017-10-01 12:34:56 PM';
		$endStr = '2017-10-08 4:32:10 AM';

		$startDate = new DateTime($startStr, $timezone);
		$endDate = new DateTime($endStr, $timezone);

		return [
			'Two date strings' => [
				$startStr,
				'2017-10-08 4:32:10 AM',
				null,
				null,
				new DateTime($startStr, $utc),
				new DateTime($endStr, $utc)
			],
			'Two DateTime objects' => [$startDate, $endDate, $timezone, null, $startDate, $endDate],
			'Two timestamps' => [
				$startDate->getTimestamp(),
				(string) $endDate->getTimestamp(),
				$timezone,
				null,
				$startDate,
				$endDate
			],
			'Date string + DateTime object' => [
				$startStr,
				$endDate,
				$timezone,
				null,
				new DateTime($startStr, $timezone),
				new DateTime($endStr, $timezone)
			],
			'null end date' => [
				$startStr,
				null,
				$timezone,
				'P1D',
				$startDate,
				(new DateTime($startStr, $timezone))->add(new DateInterval('P1D'))
			],
			'Dates input out of order' => [$endDate, $startDate, $timezone, null, $startDate, $endDate],
			'Array of strings with more than two dates' => [
				[$startStr, '2017-10-04', $endStr],
				null,
				'America/Chicago',
				null,
				$startDate,
				$endDate
			],
			'Array of DateTime objects with more than two dates' => [
				[$startDate, (new DateTime($startStr, $timezone))->add(new DateInterval('P1D')), $endDate],
				null,
				$timezone,
				null,
				$startDate,
				$endDate
			],
			'Timezone argument as string' => [$startDate, $endDate, $timezone->getName(), null, $startDate, $endDate],
			'Timezone argument as DateTimeZone object' => [$startDate, $endDate, $timezone, null, $startDate, $endDate],
			'Interval argument as string' => [$startDate, $endDate, $timezone, 'P2Y4DT6H8M', $startDate, $endDate],
			'Interval argument as DateInterval object' => [
				$startDate,
				$endDate,
				$timezone,
				new DateInterval('P2Y4DT6H8M'),
				$startDate,
				$endDate
			]
		];
	}

	/**
	 * @group constructor
	 * @expectedException \InvalidArgumentException
	 * @dataProvider provideRejectDateArgument
	 * @param $var mixed Things that are not parsable dates
	 */
	public function testRejectStartDateArgument($var): void
	{
		new DateRange($var);
	}

	/**
	 * @dataProvider provideRejectDateArgument
	 * @group constructor
	 * @expectedException \InvalidArgumentException
	 * @param $var mixed Things that are not parsable dates
	 */
	public function testRejectEndDateArgument($var): void
	{
		new DateRange('2017-10-01 12:00:00 AM', $var);
	}

	/**
	 * @return array Arguments which will trigger an \InvalidArgumentException in the DateRange constructor
	 */
	public function provideRejectDateArgument(): array
	{
		return [
			'String which cannot be converted to DateTime' => ['⛄'],
			'Empty array' => [[]],
			'Array which cannot convert to DateTime' => ['These', 'are', 'not', 'valid', 'date', 'strings']
		];
	}

	/**
	 * @group constructor
	 * @expectedException \InvalidArgumentException
	 */
	public function testRejectIntervalArgument(): void
	{
		new DateRange('2017-10-01 12:00:00 AM', null, null, '🎈');
	}

	/**
	 * @dataProvider provideAcceptTimezoneArgument
	 * @depends testAcceptArguments
	 * @group constructor
	 * @param DateTime $start Start date
	 * @param DateTime $end   End date
	 * @param string|\DateTimeZone $timezone Timezone name or `\DateTimeZone` object
	 */
	public function testAcceptTimezoneArgument(DateTime $start, DateTime $end, $timezone): void
	{
		$DateRange = new DateRange($start, $end, $timezone);

		if ($timezone instanceof DateTimeZone) {
			$timezone = $timezone->getName();
		}

		$this->assertEquals(
			$timezone,
			$DateRange->getStartDate()->getTimezone()->getName(),
			'Start dates have different timezones'
		);

		$this->assertEquals(
			$timezone,
			$DateRange->getEndDate()->getTimezone()->getName(),
			'End dates have different timezones'
		);
	}

	/**
	 * @return array Array of start and end times with a timezone string or object
	 */
	public function provideAcceptTimezoneArgument(): array
	{
		return [
			'Timezone argument as a string' => [
				new DateTime('2017-10-01 05:30:45 PM', new \DateTimeZone('Antarctica/Casey')),
				new DateTime('2017-10-08 01:11:22 AM', new \DateTimeZone('Arctic/Longyearbyen')),
				'America/Chicago'
			],
			'Timezone argument as a DateTimeZone argument' => [
				new DateTime('2017-10-01 05:30:45 PM', new \DateTimeZone('Antarctica/Casey')),
				new DateTime('2017-10-08 01:11:22 AM', new \DateTimeZone('Arctic/Longyearbyen')),
				new DateTimeZone('America/Chicago')
			]
		];
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @group constructor
	 */
	public function testRejectTimezoneArgument(): void
	{
		new DateRange('2017-10-01', '2017-10-02', '🍦');
	}

	/**
	 * @depends testAcceptArguments
	 * @group constructor
	 */
	public function testDefaultArguments(): void
	{
		$timezone = new DateTimeZone('America/Chicago');

		$start = 'today';
		$end = 'tomorrow';

		$today = new DateTime($start, $timezone);
		$tomorrow = new DateTime($end, $timezone);

		/// As string start date, `null` end date
		$DateRange = new DateRange($start, null, $timezone);

		$this->assertEquals($today, $DateRange->getStartDate());
		$this->assertEquals($tomorrow, $DateRange->getEndDate());

		/// As \DateTime start date, `null` end date
		$DateRange = new DateRange($today, null, $timezone);

		$this->assertEquals($today, $DateRange->getStartDate());
		$this->assertEquals($tomorrow, $DateRange->getEndDate());
	}

	/**
	 * @covers ::compare()
	 * @group compare
	 */
	public function testExclusions(): void
	{
		$timezone = new DateTimeZone('America/Chicago');
		$previousDay = new DateTime('yesterday', $timezone);
		$startDate = new DateTime('today', $timezone);
		$endDate = new DateTime('tomorrow', $timezone);

		$DateRange = new DateRange($startDate, $endDate);

		/// Compare with string
		$this->assertEquals(
			DateRange::COMPARE_BEFORE,
			$DateRange->compare(
				$previousDay->format('Y-m-d')
			),
			'Previous day is not before date range'
		);

		/// Compare with \DateTime
		$this->assertEquals(
			DateRange::COMPARE_BEFORE,
			$DateRange->compare(
				$previousDay
			),
			'Previous day is not before date range'
		);

		/// Inclusive
		$this->assertEquals(
			DateRange::COMPARE_BETWEEN,
			$DateRange->compare($startDate),
			'Start date is not within date range'
		);

		$this->assertEquals(
			DateRange::COMPARE_BETWEEN,
			$DateRange->compare($endDate),
			'End date is not within date range'
		);

		/// Exclusions
		$this->assertEquals(
			DateRange::COMPARE_BEFORE,
			$DateRange->compare($startDate, DateRange::EXCLUDE_START_DATE),
			'DateRange contains start date'
		);

		$this->assertEquals(
			DateRange::COMPARE_AFTER,
			$DateRange->compare($endDate, DateRange::EXCLUDE_END_DATE),
			'DateRange contains end date'
		);
	}

	/**
	 * @covers ::getInterval()
	 * @group interval
	 * @dataProvider provideInterval
	 * @param string|int|\DateTime      $start    Start date
	 * @param string|int|\DateTime|null $end      End date
	 * @param string|null|\DateTimeZone $timezone Date range timezone
	 * @param string|\DateInterval      $interval Date range iterator interval
	 * @param \DateInterval             $expected Expected `\DateInterval` value
	 */
	public function testGetInterval($start, $end, $timezone, $interval, DateInterval $expected): void
	{
		$DateRange = new DateRange($start, $end, $timezone, $interval);

		$this->assertEquals(
			$expected->d,
			$DateRange->getInterval()->d
		);
	}

	/**
	 * @covers ::getInterval()
	 * @covers ::setInterval()
	 * @group interval
	 * @dataProvider provideInterval
	 * @param string|int|\DateTime      $start    Start date
	 * @param string|int|\DateTime|null $end      End date
	 * @param string|null|\DateTimeZone $timezone Date range timezone
	 * @param string|\DateInterval      $interval Date range iterator interval,
	 * @param \DateInterval             $expected Expected DateInterval value
	 */
	public function testSetInterval($start, $end, $timezone, $interval, DateInterval $expected): void
	{
		$DateRange = new DateRange($start, $end, $timezone);

		$this->assertNotEquals(
			$expected->d,
			$DateRange->getInterval()->d,
			'Comparison interval and date range interval have the same number of days'
		);

		$DateRange->setInterval($interval);

		$this->assertEquals(
			$expected->d,
			$DateRange->getInterval()->d,
			'Date range interval did not change after `DateRange::setInterval()`'
		);
	}

	/**
	 * @return array Interval strings and objects
	 */
	public function provideInterval(): array
	{
		$timezone = new DateTimeZone('America/Chicago');

		return [
			'Interval as a string' => [
				new DateTime('2017-10-01 01:30:00 AM', $timezone),
				new DateTime('2017-10-08 05:45:10 PM', $timezone),
				$timezone,
				'P7D',
				new DateInterval('P7D')
			],
			'Interval as DateInterval object' => [
				/// Input interval object
				new DateTime('2017-10-01 01:30:00 AM', $timezone),
				new DateTime('2017-10-08 05:45:10 PM', $timezone),
				$timezone,
				new DateInterval('P2D'),
				new DateInterval('P2D')
			]
		];
	}

	/**
	 * @covers ::diff()
	 */
	public function testDiff(): void
	{
		$DateRange = new DateRange('2017-10-13', '2017-10-16');

		$this->assertEquals(
			$DateRange->diff()->format('%R%a days'),
			'+3 days'
		);
	}

	/**
	 * @covers ::__toString()
	 * @covers ::toString()
	 */
	public function testToString(): void
	{
		$DateRange = new DateRange('2017-10-01', '2017-10-31');

		$this->assertEquals(
			'10-01-2017 to 2017.10.31',
			$DateRange->toString('m-d-Y', 'Y.m.d', '%s to %s'),
			'Method DateRange::toString() output did not match expected format'
		);

		$this->assertEquals(
			'2017-10-01 - 2017-10-31',
			(string) $DateRange,
			'Casting DateRange as string did not output in the expected format'
		);
	}

	/**
	 * @covers ::toArray()
	 * @covers ::getDatePeriod()
	 * @covers ::getIterator()
	 */
	public function testToArray(): void
	{
		$timezone = new DateTimeZone('America/Chicago');

		$DateRange = new DateRange(
			'October 1 2017',
			'October 4 2017',
			$timezone
		);

		$this->assertEquals(
			[
				'Sunday',
				'Monday',
				'Tuesday',
				'Wednesday'
			],
			$DateRange->toArray('l'),
			'DateRange array does not match expected date format'
		);

		$expected = [
			new DateTime('Oct. 1, 2017', $timezone),
			new DateTime('Oct. 2, 2017', $timezone),
			new DateTime('Oct. 3, 2017', $timezone),
			new DateTime('Oct. 4, 2017', $timezone)
		];

		$this->assertEquals(
			$expected,
			$DateRange->toArray(),
			'DateRange array does not contain matching DateTime objects'
		);

		$this->assertEquals(
			[
				'10-01-2017',
				'10-04-2017'
			],
			$DateRange->toArray('m-d-Y', true),
			'DateRange short array output does not match the expected format'
		);

		/// Test iterator
		$results = [];

		foreach ($DateRange as $date) {
			$results[] = $date;
		}

		$this->assertEquals(
			$expected,
			$results,
			'DateRange array from iterator did not produce expected results'
		);
	}
}
