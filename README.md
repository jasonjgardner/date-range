# DateRange
Simple Date Range Object for PHP __7.1__

*__Earlier PHP versions__: Check out the original [DateRange](https://github.com/brtriver/date-range) by [brtriver](https://github.com/brtriver/).*

## Requirements
PHP 7.1.0 or later

## Install

Install using [Composer](https://getcomposer.org):

```bash
$ composer require jasonjgardner/date-range
```

## Usage

Create a date range object which contains a start `\DateTime` and an end `\DateTime`:

```php
use jasonjgardner\DateRange\DateRange;
$summer = new DateRange('2017-06-20', '2017-09-22');

printf('Summer starts on %s', $summer->getStartDate()->format('F j, Y'));
/// Summer starts on June 21, 2017

printf('The last day of summer is %s', $summer->getEndDate()->format('F j, Y'));
/// The last day of summer is September 22, 2017
```

Compare dates to the date range:

```php
$aries = new DateRange('March 21', 'April 19');
$birthday = new \DateTime('April 1');

if ($aries->compare($birthday) === 0) {
	echo 'You are an Aries ♈';
} else if ($aries->compare('March 1') < 0) {
	echo 'You look like a Pisces ♓';
} else if ($aries->compare('May 1') > 0) {
	echo 'Are you a Taurus? ♉';
}
```

> The `DateRange::compare()` method compares a date string, object, or timestamp against the start and end dates in the
date range. It will return __-1__ if the date is *before* the date range, __0__ if the date is *during* the date range,
or __1__ if the date is *after* the date range.

> The class constants `DateRange::COMPARE_BEFORE`, `DateRange::COMPARE_BETWEEN`, and `DateRange::COMPARE_AFTER` are set to
-1, 0, and 1 (respectively).

Pass a variety of variable types to the constructor:

```php
/// Accepts `\DateTime` objects
new DateRange(new \DateTime('today'), new \DateTime('tomorrow'));

/// Accepts date strings
new DateRange('2017-09-15', '10-15-2017');

/// Accepts timestamps
$DateRange = new DateRange(1493886600, '1499172300');
echo $DateRange->toString('m/d/Y'); /// 05/04/2017 - 07/04/2017

/// Accepts an array of dates
$dates = [
	'2017-10-21',
	'2017-01-01',
	'2017-12-31',
	'2017-10-31'
];

$DateRange = new DateRange($dates);
echo $DateRange->getStartDate()->format('M j'); /// Jan 1
echo $DateRange->getEndDate()->format('M j'); /// Dec 31

/// Only requires a start date argument
$DateRange = new DateRange('12/31/2017');
echo $DateRange->getEndDate()->format('M j, Y G:i e'); /// Jan 1, 2018 0:00 UTC

/// Create dates in a certain timezone
$date = new \DateTime(
	'March 1, 2017 3:30 PM',
	new \DateTimeZone('America/New_York')
);

$DateRange = new DateRange(
	$date,
	null,
	new \DateTimeZone('Asia/Tokyo')
);

echo $DateRange->getStartDate()->format('M j, Y G:i e'); /// March 2, 2017 4:30 AM Asia/Tokyo
```

Format dates in range:

```php
$week = new DateRange('Sunday', 'Saturday');
$days = $week->toArray('l');

/// $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

$LaborDayWeekend = new DateRange('August 31, 2018', 'September 3, 2018');
echo $LaborDayWeekend->toString('m-d-Y'); /// 08-31-2018 - 09-03-2018
echo $LaborDayWeekend->toString('m-d-Y', 'Y-n-j'); /// 08-31-2018 - 2018-9-3
echo (string) $LaborDayWeekend; /// 2018-08-31 - 2018-09-03

foreach ($LaborDayWeekend as $day) {
	echo $day->format('M j, Y');
	/// Aug 31, 2018
	/// Sep 1, 2018
	/// Sep 2, 2018
	/// Sep 3, 2018
}
```

Find the difference between the start and end dates:

```php
$DateRange = new DateRange('Nov 4', 'Nov 8');
echo $DateRange->diff()->format('%d days); /// 4 days 
```

## Demo

Try DateRange using PHP's built-in web server.

```bash
cd demo
php -S localhost:8000
```

## License
DateRange is licensed under the MIT license.
