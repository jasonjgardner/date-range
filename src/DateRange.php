<?php declare(strict_types=1);
/**
 * PHP Date Range
 * @version 1.0.0
 * @license MIT
 */

namespace jasonjgardner\DateRange;

use DateTime,
	DateInterval,
	DatePeriod,
	IteratorAggregate,
	Exception,
	InvalidArgumentException,
	LogicException;

/**
 * Date Range object
 * @package jasonjgardner\DateRange
 */
class DateRange implements IteratorAggregate
{
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
	 * Time period between start and end dates
	 * @var \DateInterval
	 */
    private $interval;

	/**
	 * Flag to exclude start date in date period
	 * @var bool
	 */
    private $excludeStartDate = false;

	/**
	 * Flag to exclude end date in date period
	 * @var bool
	 */
    private $excludeEndDate = false;

	/**
	 * Default interval to use when determining date range period
	 */
    private const INTERVAL = 'P1D';

	/**
	 * DateRange constructor.
	 * Pairs two `\DateTime` objects as a date range
	 *
	 * @param array|string|int|\DateTime $startDate Earliest date to include in range.
	 * @param string|int|null|\DateTime $endDate Latest date to include in range. May be omitted if `$startDate` is
	 *                                           an array with at least two elements.
	 * @throws \Exception An `\Exception` is thrown if `\DateInterval` cannot parse `\jasonjgardner\DateRange::INTERVAL`
	 */
    public function __construct($startDate, $endDate = null) {
    	if (is_array($startDate) && count($startDate) > 1) {
    		[$startDate, $endDate] = $startDate;
		}

    	$this->startDate = $this->parseDate($startDate);
		$this->endDate = $this->parseDate($endDate);

		/// Reverse times if end date is earlier than start date
		if ($this->startDate > $this->endDate) {
			$tmp = $this->startDate;
			$this->startDate = $this->endDate;
			$this->endDate = $tmp;
		}

        $this->interval = new DateInterval(self::INTERVAL);
    }

	/**
	 * Creates a `\DateTime` object from a timestamp or string. Returns existing `\DateTime` objects as-is.
	 * @param \DateTime|int|string $date Date object, timestamp, or date string
	 *
	 * @return \DateTime A `\DateTime` object set to the date defined in the `$date` parameter
	 * @throws \InvalidArgumentException if `$date` can not be parsed by `\DateTime::__construct`
	 */
    private function parseDate($date): DateTime
	{
		if ($date instanceof DateTime) {
			return $date;
		}

		if (is_numeric($date)) {
			return new DateTime('@' . max(0, (float) $date));
		}

		if (strtotime($date) === false) {
			throw new InvalidArgumentException(
				'Can not parse date. Date must be an instance of `\\DateTime`, a numeric timestamp, or a date string.'
			);
		}

		try {
			return new DateTime($date);
		} catch (Exception $e) {
			throw new InvalidArgumentException('Could not parse date string.', 0, $e);
		}
	}

	/**
	 * Sets flag to exclude start date in date period
	 * @return \jasonjgardner\DateRange\DateRange
	 * @throws \LogicException if the end date has already been excluded
	 */
    public function excludeStartDate(): self
    {
    	if ($this->excludeEndDate) {
    		throw new LogicException('Can not exclude start date. Only one date may be excluded.');
		}

        $this->excludeStartDate = true;

    	return $this;
    }

	/**
	 * Sets flag to exclude end date in date period
	 * @return \jasonjgardner\DateRange\DateRange
	 * @throws \LogicException if the start date has already been excluded
	 */
    public function excludeEndDate(): self
    {
		if ($this->excludeEndDate) {
			throw new LogicException('Can not exclude end date. Only one date may be excluded.');
		}

        $this->excludeEndDate = true;

		return $this;
    }

	/**
	 * Define the interval to use when determining the date range period
	 * @param \DateInterval $interval
	 *
	 * @return \jasonjgardner\DateRange\DateRange
	 */
    public function setInterval(DateInterval $interval): self
    {
        $this->interval = $interval;

        return $this;
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
	 * Gets the date period between the start and end dates
	 * @param \DateInterval|null $interval Interval between dates. Defaults to `$this->interval` if `null`
	 *
	 * @return \DatePeriod Date period between date range dates
	 */
    public function getDatePeriod(?DateInterval $interval = null): DatePeriod
    {
		$endDate = $this->endDate;

        if (!$this->excludeEndDate) {
            $endDate = clone $this->endDate;
            /// DatePeriod does not include end date so, plus 1 sec to end date.
            $endDate->modify('+1 sec');
        }

        $option = null;

        if ($this->excludeStartDate) {
            $option = DatePeriod::EXCLUDE_START_DATE;
        }

        return new DatePeriod(
        	$this->startDate,
			$interval ?? $this->interval,
			$endDate,
			$option
		);
    }

	/**
	 * @return \DatePeriod
	 */
    public function getIterator(): DatePeriod
    {
        return $this->getDatePeriod();
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
	 * Checks if a date is within the date range
	 * @param string|int|\DateTime $date A date to compare to the start and end dates
	 *
	 * @return bool Returns `true` if `$date` is between the start and end dates
	 * @throws \InvalidArgumentException if `$date` cannot be parsed
	 */
    public function contains($date): bool
    {
    	$date = $this->parseDate($date);

		$isAfterThanStart = $this->startDate <= $date;
		$isBeforeThanEnd = $date <= $this->endDate;

        if ($this->excludeStartDate) {
            $isAfterThanStart = $this->startDate < $date;
        }

        if ($this->excludeEndDate) {
            $isBeforeThanEnd = $date < $this->endDate;
        }

        return $isAfterThanStart && $isBeforeThanEnd;
    }

	/**
	 * Output date range dates as a formatted date string
	 * @param string $format    PHP date format
	 * @param string $separator String to place between formatted dates
	 *
	 * @return string Formatted date range dates
	 */
    public function toString(string $format = 'Y-m-d', string $separator = '~'): string
    {
        return sprintf(
        	'%s %s %s',
			$this->startDate->format($format),
			$separator,
			$this->endDate->format($format)
		);
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
}
