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

	private function assertSameTime(DateTime $dateX, DateTime $dateY, ?string $message = null): void
	{
		$this->assertEquals(
			$dateX->format(DateTime::W3C),
			$dateY->format(DateTime::W3C),
			$message
		);
	}

	private function assertSameTimezone(DateTime $dateX, DateTime $dateY, ?string $message = null): void
	{
		$this->assertEquals(
			$dateX->getTimezone(),
			$dateY->getTimezone(),
			$message
		);
	}

	private function assertSameDay(DateTime $dateX, DateTime $dateY, ?string $message = null): void
	{
		$this->assertEquals(
			$dateX->format('Ymd'),
			$dateY->format('Ymd'),
			$message
		);
	}

	/**
	 * @group constructor
	 */
	public function testAcceptArguments(): void
	{
		/// As two string arguments
		$DateRange = new DateRange($this->startStr, $this->endStr, $this->timezone);

		$this->assertSameTime(
			$this->startDate,
			$DateRange->getStartDate(),
			'Start dates are not the same time'
		);

		$this->assertSameTime(
			$this->endDate,
			$DateRange->getEndDate(),
			'End dates are not the same time'
		);

		/// As two \DateTime arguments
		$DateRange = new DateRange(
			new DateTime($this->startStr, $this->timezone),
			new DateTime($this->endStr, $this->timezone),
			$this->timezone
		);

		$this->assertSameDay($this->startDate, $DateRange->getStartDate());
		$this->assertSameTime($this->endDate, $DateRange->getEndDate());

		/// As an array of strings
		$DateRange = new DateRange([$this->startStr, $this->endStr], null, $this->timezone);

		$this->assertSameTime($this->startDate, $DateRange->getStartDate());
		$this->assertSameTime($this->endDate, $DateRange->getEndDate());

		/// As an array of \DateTime
		$DateRange = new DateRange([new DateTime($this->startStr), new DateTime($this->endStr)], null, $this->timezone);

		$this->assertSameTime($this->startDate, $DateRange->getStartDate());
		$this->assertSameTime($this->endDate, $DateRange->getEndDate());

		/// As one string, one \DateTime
		$DateRange = new DateRange($this->startStr, $this->endDate, $this->timezone);

		$this->assertSameTime($this->startDate, $DateRange->getStartDate());
		$this->assertSameTime($this->endDate, $DateRange->getEndDate());
	}

	/**
	 * @group constructor
	 */
	public function testTimezoneArgument(): void
	{
		$startDate = clone $this->startDate;
		$startDate->setTimezone(new \DateTimeZone('Antarctica/Casey'));

		$endDate = clone $this->endDate;
		$endDate->setTimezone(new \DateTimeZone('Arctic/Longyearbyen'));

		$DateRange = new DateRange($startDate, $endDate, $this->timezone);

		$this->assertSameTimezone(
			$startDate,
			$DateRange->getStartDate(),
			'Start dates have different timezones'
		);

		$this->assertSameTimezone(
			$endDate,
			$DateRange->getEndDate(),
			'End dates have different timezones'
		);
	}

	/**
	 * @group constructor
	 */
	public function testDefaultArguments(): void
	{
		$start = 'today';
		$end = 'tomorrow';

		$today = new DateTime($start, $this->timezone);
		$tomorrow = new DateTime($end, $this->timezone);

		/// As string start date, `null` end date
		$DateRange = new DateRange($start, null, $this->timezone);

		$this->assertSameTime($today, $DateRange->getStartDate());
		$this->assertSameTime($tomorrow, $DateRange->getEndDate());

		/// As \DateTime start date, `null` end date
		$DateRange = new DateRange($today, null, $this->timezone);

		$this->assertSameTime($today, $DateRange->getStartDate());
		$this->assertSameTime($tomorrow, $DateRange->getEndDate());
	}

	/**
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
			$DateRange->compare($this->startDate, DateRange::EXCLUDE_END_DATE),
			'DateRange contains end date'
		);
	}

	/**
	 * @group compare
	 */
	public function testComparisons(): void
	{
		$DateRange = new DateRange($this->startDate, $this->endDate);

		$this->assertTrue(
			$DateRange->isBefore($this->nextDay),
			'DateRange is not before nextDay'
		);

		$this->assertTrue(
			$DateRange->isAfter($this->nextDay),
			'DateRange is not after nextDay'
		);

		$this->assertTrue(
			$DateRange->contains($this->startDate),
			'DateRange does not contain start date'
		);

		$this->assertFalse(
			$DateRange->contains($this->startDate, DateRange::EXCLUDE_START_DATE),
			'DateRange contains $this->startDate when the start date is excluded'
		);

		$this->assertTrue(
			$DateRange->contains($this->endDate),
			'DateRange does not contain end date'
		);

		$this->assertFalse(
			$DateRange->contains($this->endDate, DateRange::EXCLUDE_END_DATE),
			'DateRange contains $this->endDate when the end date is excluded'
		);
	}
}
