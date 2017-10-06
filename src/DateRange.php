<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.0
 * @license MIT
 */

namespace jasonjgardner\DateRange;

use DateTime,
	DateTimeZone,
	DateInterval,
	DatePeriod,
	IteratorAggregate,
	Exception,
	InvalidArgumentException;

/**
 * Date Range object
 * @package jasonjgardner\DateRange
 */
class DateRange implements IteratorAggregate
{
	/**
	 * Flag to make date range exclude the start date
	 */
	const EXCLUDE_START_DATE = 0b0001;

	/**
	 * Flag to make the date range exclude the end date
	 */
	const EXCLUDE_END_DATE = 0b0010;

	/**
	 * Integer result to use when `DateRange::compare()` finds a date *earlier* than this date range
	 * @see \jasonjgardner\DateRange\DateRange::compare()
	 */
	const COMPARE_BEFORE = -1;

	/**
	 * Integer result to use when `DateRange::compare()` finds a date *between* this date range
	 * @see \jasonjgardner\DateRange\DateRange::compare()
	 */
	const COMPARE_BETWEEN = 0;

	/**
	 * Integer result to use when `DateRange::compare()` finds a date *later* than this date range
	 * @see \jasonjgardner\DateRange\DateRange::compare()
	 */
	const COMPARE_AFTER = 1;

	/**
	 * Earliest date in date range
	 * @var \DateTime
	 */
	private $startDate;

	/**
	 * Latest date in date range
	 * @var \DateTime
	 */
	private $endDate;

	/**
	 * Interval used when iterating over date range or getting date period
	 * @var \DateInterval
	 */
	private $interval;

	/**
	 * DateRange constructor.
	 * Pairs two `\DateTime` objects as a date range
	 *
	 * @param array|string|int|\DateTime $startDate Earliest date to include in range.
	 * @param string|int|null|\DateTime  $endDate   Latest date to include in range. May be omitted if `$startDate` is
	 *                                              an array with at least two elements.
	 * @param string|\DateTimeZone       $timezone  Timezone to use when initializing date range dates
	 * @param string|\DateInterval       $interval  Date interval used in iterator and date period references
	 *
	 * @throws \InvalidArgumentException if parameters could not be parsed by `\DateTime`, `\DateInterval`, or `\DateTimeZone`
	 */
	public function __construct($startDate, $endDate = null, $timezone = 'UTC', $interval = 'P1D')
	{
		if (!$timezone instanceof DateTimeZone) {
			try {
				$timezone = new DateTimeZone($timezone);
			} catch (\Exception $e) {
				throw new \InvalidArgumentException(
					'Timezone argument must be an instance of `\\DateTimeZone` or a supported PHP timezone name string.'
				);
			}
		}

		try {
			if (!$interval instanceof DateInterval) {
				$interval = new DateInterval($interval);
			}
		} catch (\Exception $e) {
			throw new \InvalidArgumentException(
				'Could not parse date range interval. Argument must be an instance of `\\DateInterval` or a
				well-formatted interval string.',
				null,
				$e
			);
		}

		$this->interval = $interval;

		if (is_array($startDate) && count($startDate) > 1) {
			[$startDate, $endDate] = $startDate;
		}

		try {
			$this->startDate = $this->parseDate($startDate, $timezone);

			if ($endDate === null) {
				$endDate = clone $this->startDate;
				$endDate->add($this->interval);
			}

			$this->endDate = $this->parseDate($endDate, $timezone);

		} catch (\Exception $e) {
			throw new \InvalidArgumentException(
				'Could not parse date range dates. Arguments must be instances of `\\DateTime` or well-formatted date
				strings.',
				$e->getCode(),
				$e
			);
		}

		/// Reverse times if end date is earlier than start date
		if ($this->startDate > $this->endDate) {
			[$this->startDate, $this->endDate] = [$this->endDate, $this->startDate];
		}
	}

	/**
	 * Creates a `\DateTime` object from a timestamp or string. Returns existing `\DateTime` objects as-is.
	 *
	 * @param \DateTime|int|string $date     Date object, timestamp, or date string
	 * @param \DateTimeZone|null   $timezone Optional timezone to use in `\DateTime` constructor
	 *
	 * @return \DateTime A `\DateTime` object set to the date defined in the `$date` parameter
	 * @throws \InvalidArgumentException if `$date` can not be parsed by `\DateTime::__construct`
	 */
	private function parseDate($date, ?DateTimeZone $timezone = null): DateTime
	{
		if ($date instanceof DateTime) {
			if ($timezone !== null) {
				$date->setTimezone($timezone);
			}

			return $date;
		}

		if (is_numeric($date)) {
			return new DateTime('@' . max(0, (float) $date), $timezone);
		}

		if (!is_string($date) || strtotime($date) === false) {
			throw new InvalidArgumentException(
				'Can not parse date. Date must be an instance of `\\DateTime`, a numeric timestamp, or a date string.'
			);
		}

		try {
			return new DateTime($date, $timezone);
		} catch (Exception $e) {
			throw new InvalidArgumentException('Could not parse date string.', 0, $e);
		}
	}

	/**
	 * Gets the `\DateInterval` instance used in determining the date range period
	 * @return \DateInterval
	 */
	public function getInterval(): DateInterval
	{
		return $this->interval;
	}

	/**
	 * Define the interval to use when determining the date range period
	 *
	 * @param string|\DateInterval $interval `\DateInterval` instance or the interval spec string
	 *
	 * @return \jasonjgardner\DateRange\DateRange
	 * @throws \Exception if `$interval` is an invalid spec string
	 */
	public function setInterval($interval): self
	{
		if (!$interval instanceof DateInterval) {
			$interval = new DateInterval((string) $interval);
		}

		$this->interval = $interval;

		return $this;
	}

	/**
	 * Allow iterating over this date range based on the `\DateInterval` stored in `$this->interval`
	 * @return \DatePeriod
	 */
	public function getIterator(): DatePeriod
	{
		return $this->getDatePeriod();
	}

	/**
	 * Gets the date period between the start and end dates
	 *
	 * @param \DateInterval|null $interval Interval between dates. Defaults to `$this->interval` if `null`
	 * @param int|null           $exclude  Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                     the start and end dates themselves.
	 *
	 * @return \DatePeriod Date period between date range dates
	 */
	public function getDatePeriod(?DateInterval $interval = null, ?int $exclude = null): DatePeriod
	{
		$endDate = $this->endDate;

		if (~$exclude & self::EXCLUDE_END_DATE) {
			$endDate = clone $this->endDate;
			/// DatePeriod does not include end date so, plus 1 sec to end date.
			$endDate->modify('+1 sec');
		}

		$options = null;

		if ($exclude & self::EXCLUDE_START_DATE) {
			$options = DatePeriod::EXCLUDE_START_DATE;
		}

		return new DatePeriod(
			$this->startDate,
			$interval ?? $this->interval,
			$endDate,
			$options
		);
	}

	/**
	 * Get the earliest date as a `\DateTime` object
	 * @return \DateTime
	 */
	public function getStartDate(): DateTime
	{
		return $this->startDate;
	}

	/**
	 * Get the latest date as a `\DateTime` object
	 * @return \DateTime
	 */
	public function getEndDate(): DateTime
	{
		return $this->endDate;
	}

	/**
	 * Get the difference between the start and end dates
	 * @return \DateInterval `\DateTime` difference
	 */
	public function diff(): DateInterval
	{
		return $this->startDate->diff($this->endDate);
	}



	/**
	 * Compares a date to this date range and determines if it falls before, after, or between this date range.
	 *
	 * @param string|int|\DateTime $date    A date to compare to the start and end dates
	 * @param int|null             $exclude Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                      the start and end dates themselves.
	 *
	 * @return int Returns `-1` if `$date` is before this date range, `1` if it's after, or `0` if it's between dates
	 * @throws \InvalidArgumentException if `$date` can not be parsed
	 */
	public function compare($date, ?int $exclude = null): int
	{
		$date = $this->parseDate($date);

		$isLaterThanStart = $this->startDate <= $date;
		$isEarlierThanEnd = $date <= $this->endDate;

		if ($exclude & self::EXCLUDE_START_DATE) {
			$isLaterThanStart = $this->startDate < $date;
		}

		if ($exclude & self::EXCLUDE_END_DATE) {
			$isEarlierThanEnd = $date < $this->endDate;
		}

		if ($isLaterThanStart && $isEarlierThanEnd) {
			return self::COMPARE_BETWEEN;
		}

		if (!$isLaterThanStart) {
			return self::COMPARE_BEFORE;
		}

		return self::COMPARE_AFTER;
	}

	/**
	 * Return formatted date range when cast as string
	 * @return string Formatted date range dates
	 * @uses \jasonjgardner\DateRange\DateRange::toString()
	 */
	public function __toString(): string
	{
		return $this->toString();
	}

	/**
	 * Creates a string representing the date range
	 *
	 * @param string      $startFormat Format in which to display the start date
	 * @param null|string $endFormat   Format in which to display the end date. Defaults to `$startDate` if `null`
	 * @param string      $strFormat   `sprintf` format used to combine dates
	 *
	 * @return string Start and end date formatted according to parameters
	 */
	public function toString(string $startFormat = 'Y-m-d', ?string $endFormat = null, string $strFormat = '%s - %s'): string
	{
		return sprintf(
			$strFormat,
			$this->startDate->format($startFormat),
			$this->endDate->format($endFormat ?? $startFormat)
		);
	}
}
