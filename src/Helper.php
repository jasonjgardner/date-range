<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.0
 * @license MIT
 */
namespace jasonjgardner\DateRange;

use DateTime,
	Exception,
	InvalidArgumentException;

/**
 * Helper methods
 * @package jasonjgardner\DateRange
 */
class Helper
{
	/**
	 * @var \jasonjgardner\DateRange\DateRange
	 */
	private $dateRange;

	/**
	 * Helper constructor.
	 *
	 * @param \jasonjgardner\DateRange\DateRange $dateRange
	 */
	public function __construct(DateRange $dateRange)
	{
		$this->dateRange = $dateRange;
	}

	/**
	 * Return `$this->dateRange` to an array
	 *
	 * @param null|string $format When set, the array is an array of formatted strings. When `null`, it is an array of
	 *                            `\DateTime` objects
	 * @param bool        $short When `true`, the array only contains the start and end dates
	 *
	 * @return array `\DateTime` objects or formatted date strings
	 * @throws \InvalidArgumentException Thrown when `$format` is not a valid PHP date format string
	 */
	public function asArray(?string $format = null, bool $short = false): array
	{
		return self::toArray($this->dateRange, $format, $short);
	}

	/**
	 * Converts a date range to an array
	 *
	 * @static
	 *
	 * @param \jasonjgardner\DateRange\DateRange $dateRange `DateRange` to convert
	 * @param null|string                        $format    When set, the array is an array of formatted strings. When
	 *                                                      `null`, it is an array of `\DateTime` objects
	 * @param bool                               $short     When `true`, the array only contains the start and end dates
	 *
	 * @return array `\DateTime` objects or formatted date strings
	 * @throws \InvalidArgumentException Thrown when `$format` is not a valid PHP date format string
	 */
	public static function toArray(DateRange $dateRange, ?string $format = null, bool $short = false): array
	{
		$dates = ($short !== true) ? iterator_to_array($dateRange) : [
			$dateRange->getStartDate(),
			$dateRange->getEndDate()
		];

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

	/**
	 * Checks if a date is within the date range. Date range includes the start and end dates by default.
	 *
	 * @param string|int|\DateTime $date    A date to compare to the start and end dates
	 * @param int|null             $exclude Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                      the start and end dates themselves.
	 *
	 * @return bool Returns `true` if `$date` is between the start and end dates
	 * @throws \InvalidArgumentException if `$date` cannot be parsed
	 */
	public function contains($date, ?int $exclude = null): bool
	{
		return $this->dateRange->compare($date, $exclude) === DateRange::COMPARE_BETWEEN;
	}

	/**
	 * Checks if a date is before the date range. Date range includes the start and end dates by default.
	 *
	 * @param string|int|\DateTime $date    A date to compare to the start and end dates
	 * @param int|null             $exclude Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                      the start and end dates themselves.
	 *
	 * @return bool Returns `true` if `$date` is before the start and end dates
	 * @throws \InvalidArgumentException if `$date` cannot be parsed
	 */
	public function isAfter($date, ?int $exclude = null): bool
	{
		return $this->dateRange->compare($date, $exclude | DateRange::EXCLUDE_START_DATE) === DateRange::COMPARE_BEFORE;
	}

	/**
	 * Checks if a date is after the date range. Date range includes the start and end dates by default.
	 *
	 * @param string|int|\DateTime $date    A date to compare to the start and end dates
	 * @param int|null             $exclude Optional bit flag which sets the date range to be inclusive or exclusive of
	 *                                      the start and end dates themselves.
	 *
	 * @return bool Returns `true` if `$date` is after the start and end dates
	 * @throws \InvalidArgumentException if `$date` cannot be parsed
	 */
	public function isBefore($date, ?int $exclude = null): bool
	{
		$ex = $exclude | DateRange::EXCLUDE_END_DATE;
		return $this->dateRange->compare($date, $ex) === DateRange::COMPARE_AFTER;
	}

	/**
	 * Check if a date range is on a weekend
	 * @static
	 * @param DateRange $dateRange The date range which might be a weekend
	 * @return bool Returns `true` if start date is a Saturday and end date is the following Sunday, otherwise `false`
	 */
	public static function isWeekend(DateRange $dateRange): bool
	{
		if ($dateRange->diff()->days !== 1) {
			return false;
		}

		return ((int) $dateRange->getStartDate()->format('w') === 6)
			&& ((int) $dateRange->getEndDate()->format('w') === 0);
	}

	/**
	 * Check if this date range is on a weekend
	 * @return bool
	 */
	public function onWeekend(): bool
	{
		return self::isWeekend($this->dateRange);
	}
}
