<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.1
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
	 * @throws InvalidArgumentException if parameters could not be parsed by `\DateTime`, `\DateInterval`, or
	 *                                  `\DateTimeZone`
	 */
	public function __construct($startDate, $endDate = null, $timezone = null, $interval = null)
	{
		$timezone = self::instantiate(
			$timezone,
			DateTimeZone::class,
			'Timezone argument must be an instance of `\\DateTimeZone` or a supported PHP timezone name 
			string.'
		) ?? new DateTimeZone('UTC');

		if (is_array($startDate)) {
			/// Find the earliest and latest dates in the array
			[$startDate, $endDate] = self::getBoundaries($startDate, $timezone);
		}

		try {
			$this->interval = self::instantiate(
				$interval,
				DateInterval::class,
				'Could not parse date range interval. Argument must be an instance of `\\DateInterval`
				or a well-formatted interval string.'
			) ?? new DateInterval('P1D');

			$this->startDate = self::toDate($startDate, $timezone);

			if ($endDate === null) {
				$endDate = clone $this->startDate;
				$endDate->add($this->interval);
			}

			$this->endDate = self::toDate($endDate, $timezone);
		} catch (Exception $e) {
			throw new InvalidArgumentException(
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
	 * Parse an array of dates and return the start and end dates as `\DateTime` objects
	 *
	 * @static
	 *
	 * @param array         $dates    Array of `\DateTime` objects, timestamp intervals, or parsable date strings
	 * @param \DateTimeZone $timezone The timezone to apply to parsed dates
	 *
	 * @return array An array containing a `\DateTime` object for the start date, and a `\DateTime` or `null` value as
	 *               the end date.
	 * @throws \InvalidArgumentException if the `$dates` array contains no parsable dates or `\DateTime` objects
	 */
	private static function getBoundaries(array $dates, DateTimeZone $timezone): array
	{
		$filtered = array_filter(
			$dates,
			function ($item): bool {
				/// Filter out items which could trigger errors while parsing dates
				return (
					$item instanceof DateTime
					|| is_numeric($item)
					|| (is_string($item) && strtotime($item) !== false)
				);
			}
		);

		$dateCount = count($filtered);

		if ($dateCount < 1) {
			throw new InvalidArgumentException(
				'Could not convert array to dates. The array does not contain any parsable dates.'
			);
		}

		$dates = array_map(
			function ($date) use ($timezone): DateTime {
				return self::toDate($date, $timezone);
			},
			$filtered
		);

		/// Sort dates in ascending order
		usort($dates, function (DateTime $thisDate, DateTime $thatDate): int {
			if ($thisDate == $thatDate) {
				return 0;
			}

			if ($thisDate < $thatDate) {
				return -1;
			}

			return 1;
		});

		/// Return a `null` end date if array is too short. Null end dates will be converted to a date one
		/// `$this->interval` after the start date.
		$endDate = null;

		if ($dateCount > 1) {
			$endDate = array_slice($dates, -1);
		}

		/// Return earliest and latest date
		return [$dates[0], $endDate[0]];
	}

	/**
	 * Class instantiation helper.
	 * If `$instance` is an instance of `$className`, the `$instance` object will be returned. Otherwise, `$instance`
	 * is passed to the `$className` constructor.
	 *
	 * @static
	 * @internal
	 *
	 * @param string|\DateTimeInterface|\DateInterval|\DateTimeZone $instance         An instance of `$className` or
	 *                                                                                the
	 *                                                                                parameters which will be passed
	 *                                                                                to the
	 *                                                                                `$className` constructor
	 * @param string                                                $className        The class name and namespace
	 *                                                                                expected to find in `$instance`
	 * @param string                                                $exceptionMessage An error message to return if
	 *                                                                                `$instance` fails to instantiate
	 *                                                                                an instance of `$className`
	 *
	 * @return null|mixed
	 * @throws InvalidArgumentException if an `\Exception` is thrown when `$instance` is passed to the `$className`
	 *                                  constructor
	 */
	private static function instantiate($instance, string $className, string $exceptionMessage = '')
	{
		if (is_a($instance, $className, false)) {
			return $instance;
		}

		if (!is_string($instance)) {
			return null;
		}

		try {
			return new $className($instance);
		} catch (Exception $e) {
			throw new InvalidArgumentException($exceptionMessage, $e->getCode(), $e);
		}
	}

	/**
	 * Creates a `\DateTime` object from a timestamp or string. Returns existing `\DateTime` objects as-is.
	 *
	 * @static
	 *
	 * @param \DateTime|int|string $date     Date object, timestamp, or date string
	 * @param \DateTimeZone|null   $timezone Optional timezone to use in `\DateTime` constructor
	 *
	 * @return \DateTime A `\DateTime` object set to the date defined in the `$date` parameter
	 * @throws InvalidArgumentException if `$date` can not be parsed by `\DateTime::__construct`
	 */
	private static function toDate($date, ?DateTimeZone $timezone = null): DateTime
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
	public function getDateInterval(): DateInterval
	{
		return $this->interval;
	}

	/**
	 * Define the interval to use when determining the date range period
	 *
	 * @param string|\DateInterval $interval `\DateInterval` instance or the interval spec string
	 *
	 * @return \jasonjgardner\DateRange\DateRange
	 * @throws Exception if `$interval` is an invalid spec string
	 */
	public function setDateInterval($interval): self
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
	 * @param int                $exclude  Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                     the start and end dates themselves.
	 *
	 * @return \DatePeriod Date period between date range dates
	 */
	public function getDatePeriod(?DateInterval $interval = null, int $exclude = 0): DatePeriod
	{
		$endDate = $this->endDate;

		if (~$exclude & self::EXCLUDE_END_DATE) {
			$endDate = clone $this->endDate;
			/// DatePeriod does not include end date so, plus 1 sec to end date.
			$endDate->modify('+1 sec');
		}

		$options = 0;

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
	 * @param int                  $exclude Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                      the start and end dates themselves.
	 *
	 * @return int Returns `-1` if `$date` is before this date range, `1` if it's after, or `0` if it's between dates
	 * @throws InvalidArgumentException if `$date` can not be parsed
	 */
	public function compare($date, int $exclude = 0): int
	{
		$date = self::toDate($date);

		$isAfterStart = $this->startDate <= $date; /// Is on or after start date
		$isBeforeEnd = $date <= $this->endDate; /// Is on or before end date

		if ($exclude & self::EXCLUDE_START_DATE) {
			$isAfterStart = $this->startDate < $date; /// Is after start date
		}

		if ($exclude & self::EXCLUDE_END_DATE) {
			$isBeforeEnd = $date < $this->endDate; /// Is before end date
		}

		if ($isAfterStart && $isBeforeEnd) {
			return self::COMPARE_BETWEEN;
		}

		if (!$isAfterStart) {
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
	public function toString(
		string $startFormat = 'Y-m-d',
		?string $endFormat = null,
		string $strFormat = '%s - %s'
	): string {
		return sprintf(
			$strFormat,
			$this->startDate->format($startFormat),
			$this->endDate->format($endFormat ?? $startFormat)
		);
	}

	/**
	 * Converts date range period to array
	 *
	 * @param null|string $format When set, the array is an array of formatted strings. When
	 *                            `null`, it is an array of `\DateTime` objects
	 * @param bool        $short  When `true`, the array only contains the start and end dates
	 * @param \DateInterval|null $interval Interval between dates. Defaults to `$this->interval` if `null`. Parameter is
	 *                                     ignored if `$short` is `true`.
	 * @param int                $exclude  Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                     the start and end dates themselves. Parameter ignored if `$short` is `$true`.
	 *
	 * @return array `\DateTime` objects or formatted date strings
	 * @throws InvalidArgumentException Thrown when `$format` is not a valid PHP date format string
	 */
	public function toArray(
		?string $format = null,
		bool $short = false,
		?DateInterval $interval = null,
		int $exclude = 0
	): array {
		$dates = [
			$this->startDate,
			$this->endDate
		];

		if (!$short) {
			$dates = iterator_to_array($this->getDatePeriod($interval, $exclude));
		}

		if ($format === null) {
			return $dates;
		}

		try {
			return array_map(function (DateTime $date) use ($format): string {
				return $date->format($format);
			}, $dates);
		} catch (Exception $e) {
			throw new InvalidArgumentException('Invalid date format', 0, $e);
		}
	}
}
