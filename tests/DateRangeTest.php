<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.0
 * @license MIT
 */

namespace jasonjgardner\DateRange\Test;

use DateTime,
	DateTimeZone,
	DateInterval;

use PHPUnit\Framework\TestCase;

use jasonjgardner\DateRange\DateRange;

/**
 * DateRange test suite
 * @author Jason Gardner
 * @package jasonjgardner\DateRange\Test
 */
class DateRangeTest extends TestCase
{
	/**
	 * Start date as string
	 * @var string
	 */
	private $startStr;

	/**
	 * Start date as DateTime
	 * @var \DateTime
	 */
	private $startDate;

	/**
	 * End date as string
	 * @var string
	 */
	private $endStr;

	/**
	 * End date as DateTime
	 * @var \DateTime
	 */
	private $endDate;

	/**
	 * `\DateTime` object representing the day before `$this->startDate`
	 * @var \DateTime
	 */
	private $previousDay;

	/**
	 * `\DateTime` object representing the day after `$this->endDate`
	 * @var \DateTime
	 */
	private $nextDay;

	/**
	 * Timezone in which test dates occur
	 * @var \DateTimeZone
	 */
	private $timezone;

	public function setUp(): void
	{
		$this->timezone = new DateTimeZone('America/Chicago');

		$this->startStr = '2017-10-01 12:34:56 PM';
		$this->startDate = new DateTime($this->startStr, $this->timezone);

		$this->endStr = '2017-10-08 4:32:10 AM';
		$this->endDate = new DateTime($this->endStr, $this->timezone);

		$day = new DateInterval('P1D');

		$prev = new DateTime($this->startStr, $this->timezone);
		$this->previousDay = $prev->sub($day);

		$next = new DateTime($this->endStr, $this->timezone);
		$this->nextDay = $next->add($day);
	}

	/**
	 * Tests if the class constructor can accept a variety of date variable types
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
	 * @todo Add timestamp arguments to results
	 * @return array Arguments for `DateRangeTest::testAcceptArguments()`
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
			/// Input two date strings
			[
				$startStr,
				'2017-10-08 4:32:10 AM',
				null,
				null,
				new DateTime($startStr, $utc),
				new DateTime($endStr, $utc)
			],

			/// Input two DateTime objects
			[$startDate, $endDate, $timezone, null, $startDate, $endDate],

			/// Input one date string and one DateTime object
			[
				$startStr,
				$endDate,
				$timezone,
				null,
				new DateTime($startStr, $timezone),
				new DateTime($endStr, $timezone)
			],

			/// Input only first date to create default end date
			[
				$startStr,
				null,
				$timezone,
				'P1D',
				$startDate,
				(new DateTime($startStr, $timezone))->add(new DateInterval('P1D'))
			],

			/// Input dates out of order
			[$endDate, $startDate, $timezone, null, $startDate, $endDate],

			/// Input an array of strings
			[
				[$startStr, $endStr],
				null,
				'America/Chicago',
				null,
				$startDate,
				$endDate
			],

			/// Input array of DateTime objects
			[
				[$startDate, $endDate],
				null,
				$timezone,
				null,
				$startDate,
				$endDate
			],

			/// Input mixed array
			[
				[$startStr, $endDate],
				null,
				$timezone,
				null,
				$startDate,
				$endDate
			],

			/// Input timezone string
			[$startDate, $endDate, $timezone->getName(), null, $startDate, $endDate],

			/// Input timezone object
			[$startDate, $endDate, $timezone, null, $startDate, $endDate],

			/// Input interval string
			[$startDate, $endDate, $timezone, 'P2Y4DT6H8M', $startDate, $endDate],

			/// Input interval object
			[$startDate, $endDate, $timezone, new DateInterval('P2Y4DT6H8M'), $startDate, $endDate]
		];
	}

	/**
	 * @depends testAcceptArguments
	 * @group constructor
	 * @expectedException \InvalidArgumentException
	 */
	public function testRejectStartDateArgument(): void
	{
		new DateRange('⛄');
	}

	/**
	 * @depends testAcceptArguments
	 * @group constructor
	 * @expectedException \InvalidArgumentException
	 */
	public function testRejectEndDateArgument(): void
	{
		new DateRange('2017-10-01 12:00:00 AM', '🎷');
	}

	/**
	 * @depends testAcceptArguments
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
			[
				new DateTime('2017-10-01 05:30:45 PM', new \DateTimeZone('Antarctica/Casey')),
				new DateTime('2017-10-08 01:11:22 AM', new \DateTimeZone('Arctic/Longyearbyen')),
				'America/Chicago'
			],
			[
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
	 * @covers DateRange::compare()
	 * @group compare
	 */
	public function testExclusions(): void
	{
		$DateRange = new DateRange($this->startDate, $this->endDate);

		/// Compare with string
		$this->assertEquals(
			DateRange::COMPARE_BEFORE,
			$DateRange->compare(
				$this->previousDay->format('Y-m-d')
			),
			'Previous day is not before date range'
		);

		/// Compare with \DateTime
		$this->assertEquals(
			DateRange::COMPARE_BEFORE,
			$DateRange->compare(
				$this->previousDay
			),
			'Previous day is not before date range'
		);

		/// Inclusive
		$this->assertEquals(
			DateRange::COMPARE_BETWEEN,
			$DateRange->compare($this->startDate),
			'Start date is not within date range'
		);

		$this->assertEquals(
			DateRange::COMPARE_BETWEEN,
			$DateRange->compare($this->endDate),
			'End date is not within date range'
		);

		/// Exclusions
		$this->assertEquals(
			DateRange::COMPARE_BEFORE,
			$DateRange->compare($this->startDate, DateRange::EXCLUDE_START_DATE),
			'DateRange contains start date'
		);

		$this->assertEquals(
			DateRange::COMPARE_AFTER,
			$DateRange->compare($this->endDate, DateRange::EXCLUDE_END_DATE),
			'DateRange contains end date'
		);
	}

	/**
	 * @covers DateRange::getInterval()
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
	 * @covers DateRange::getInterval()
	 * @covers DateRange::setInterval()
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
	 * @return array
	 */
	public function provideInterval(): array
	{
		$timezone = new DateTimeZone('America/Chicago');

		return [
			[
				/// Input interval string
				new DateTime('2017-10-01 01:30:00 AM', $timezone),
				new DateTime('2017-10-08 05:45:10 PM', $timezone),
				$timezone,
				'P7D',
				new DateInterval('P7D')
			],
			[
				/// Input interval object
				new DateTime('2017-10-01 01:30:00 AM', $timezone),
				new DateTime('2017-10-08 05:45:10 PM', $timezone),
				$timezone,
				new DateInterval('P2D'),
				new DateInterval('P2D')
			]
		];
	}
}
