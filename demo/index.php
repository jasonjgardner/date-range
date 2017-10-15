<?php
/**
 * PHP Date Range
 * @license MIT
 */

use jasonjgardner\DateRange\DateRange;

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
	echo 'DateRange requires PHP version 7.1.0 or higher. Current PHP version: ' . PHP_VERSION;
	exit;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'DateRange.php';

///
/// Demo setup
/// 

define('SHOW_MAX_DATES', 7);

/**
 * Quickly check the existence of an array key
 * @param array  $arr Haystack
 * @param string $key Needle
 *
 * @return bool Returns `true` if `$key` is in `$arr`, otherwise `false`
 */
function aok(array $arr, $key)
{
	return (isset($arr[$key]) || array_key_exists($key, $arr));
}

/**
 * `print_r()` abridged
 * @param array $arr    Array to print
 * @param int   $length Number of elements to print in total
 *
 * @return mixed|string String like `print_r()` output
 * @SuppressWarnings(PHPMD)
 */
function printSlice(array $arr, $length = SHOW_MAX_DATES)
{
	$count = count($arr);
	$offset = $length - 2;

	if ($length >= $count) {
		return print_r($arr, true);
	}

	$slice = array_slice($arr, 0, $offset);

	$output = 'Array' . PHP_EOL . '(' . PHP_EOL;

	$itr = 0;

	foreach ($slice as $item) {
		$output .= sprintf("\t[%d] => %s", $itr, $item) . PHP_EOL;
		$itr++;
	}

	/// Decrease count to show the correct index
	--$count;

	$output .= "\t..." . PHP_EOL
		. "\t[{$count}] => " . array_values(array_slice($arr, -1))[0]
		. PHP_EOL . ')';

	return $output;
}

$defaultTimezone = date_default_timezone_get();
$timezones = [
	'Africa' => DateTimeZone::listIdentifiers(DateTimeZone::AFRICA),
	'America' => DateTimeZone::listIdentifiers(DateTimeZone::AMERICA),
	'Antarctica' => DateTimeZone::listIdentifiers(DateTimeZone::ANTARCTICA),
	'Arctic' => DateTimeZone::listIdentifiers(DateTimeZone::ARCTIC),
	'Asia' => DateTimeZone::listIdentifiers(DateTimeZone::ASIA),
	'Atlantic' => DateTimeZone::listIdentifiers(DateTimeZone::ATLANTIC),
	'Australia' => DateTimeZone::listIdentifiers(DateTimeZone::AUSTRALIA),
	'Europe' => DateTimeZone::listIdentifiers(DateTimeZone::EUROPE),
	'Indian' => DateTimeZone::listIdentifiers(DateTimeZone::INDIAN),
	'Pacific' => DateTimeZone::listIdentifiers(DateTimeZone::PACIFIC)
];

$timezone = new DateTimeZone($defaultTimezone);
$now = new DateTime('now', $timezone);

define('INTERVAL_SPEC', 'P1D');
$interval = new DateInterval(INTERVAL_SPEC);

$demoDates = new DateRange('Sunday', 'Saturday', $defaultTimezone, $interval);

if ($now->format('w') === '6') {
	$demoDates = new DateRange('Last Sunday', $now, $defaultTimezone, $interval);
}

$startDate = $demoDates->getStartDate();
$endDate = $demoDates->getEndDate();
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title>DateRange Demo</title>

	<link href="data:image/gif;base64,R0lGODlhEAAQAOYAAMdDQ/j5+pCgruvt8P39/m95gtNWVu3v8sdEROPs8OTo7IqaqYqbqvT19+Xp7fmSknGFl8hCQp2rt4aXpoGTo85OTnWOn3uNnufr7u+AgMlHR4h9io6erHaJmpCfrXKGmOzv8dFRUfPY2PPX15mntJuNmImZqG55gdvEx4OUpNBSUtHY3u1/f+Pn6+RxccdCQo+jsnSImdjd4vX3+OHl6ens7/r7/NNVVfL09qCuuubq7eXp7PuUlOLm6pGgrv7+/3iLnNJVVY+frfmRkfLX1+t9ff7+/m54gcZCQv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAQABAAAAeYgAAvSISFhoUAAC4sRY2Oj40VERk8Q5aXmA9DIQgqBkGgoaIGNxobKCJEqqusI0QlFglJs7S1tDBAPbU2AUYztT8+ubQBOQ5GEhi0wcNJDSQfA0kTFDuzzLpJBykx0iYQMtfC2Uk1DDo/HCvL47UgOAQKwMIttvazwjRJRvz9/kYEBHRYIECIwYMIPQi5UODEkYcQI0IsEAgAOw==" rel="icon" type="image/x-icon">

	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" rel="stylesheet"
		  integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	<link href="http://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/github.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

	<style type="text/css">
		hr {
			clear: both;
		}

		.no-js .needs-js {
			display: none;
		}

		.flatpickr {
			position: relative;
		}

		.flatpickr-wrapper {
			width: 100%;
		}
	</style>
</head>
<body>
<?php
///
/// Demo Parameters
///

try {
	if (aok($_GET, 'timezone')) {
		$timezone = new DateTimeZone($_GET['timezone']);
	}
} catch (Exception $e) {
	echo '<div class="alert alert-danger" role="alert">
		<h4 class="alert-heading">Invalid Timezone</h4>
		<p>The timezone <b>' . strip_tags(filter_var($_GET['timezone'], FILTER_SANITIZE_STRING)) . '</b> is
		not a supported PHP timezone name.</p>
		<p class="mb-0">' . $e->getMessage() . '</p></div></body></html>';
	exit;
}

try {
	if (aok($_GET, 'range')) {
		$demoDates = new DateRange(explode(' to ', $_GET['range']), null, $timezone);
	}
} catch (Exception $e) {
	echo '<div class="alert alert-danger" role="alert">
		<h4 class="alert-heading">Invalid Date Range</h4>
		<p>The date range URL parameter cannot be parsed.</p>
		<p class="mb-0">' . $e->getMessage() . '</p></div></body></html>';
	exit;
}
?>

<div class="jumbotron bg-primary text-white rounded-0">
	<div class="container">
		<div class="d-flex flex-column flex-lg-row align-items-center justify-content-md-between">
			<h1 class="display-1 mb-sm-4">DateRange</h1>

			<a class="btn btn-lg bg-white text-primary mt-sm-2" href="https://github.com/jasonjgardner/date-range" rel="noopener">
				View project on GitHub
			</a>
		</div>
		<!-- /.d-flex -->
	</div>
	<!-- /.container -->
</div>
<!-- /.jumbotron -->

<div class="container" role="main">
	<form class="needs-js mb-2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
		<div class="form-row">
			<div class="form-group col-sm-12 col-md-8 flatpickr" data-default-dates='<?php
			echo $demoDates->toString('Y-m-d H:i:s', null, '["%s", "%s"]'); ?>'>
				<label class="col-form-label" for="range">Select Dates</label>
				<input class="form-control" id="range" name="range" type="text" placeholder="Select date range"
					   aria-label="Select date range">
			</div>
			<!-- /.form-group -->
			<div class="form-group col-sm-12 col-md-4">
				<label class="col-form-label" for="timezone">Timezone</label>
				<select class="custom-select form-control" id="timezone" name="timezone">
					<option>UTC</option>
					<?php
					$selectedZone = $timezone->getName();
					foreach ($timezones as $location => $zones) {
						echo "<optgroup label=\"{$location}\">";

						foreach ((array) $zones as $zone) {
							echo '<option';

							if ($zone === $selectedZone) {
								echo ' selected';
							}

							echo ">{$zone}</option>";
						}

						echo '</optgroup>';
					}
					?>
				</select>
			</div>
			<!-- /.form-group -->
		</div>
		<!-- /.form-row -->

		<button class="btn btn-primary float-right px-4 mb-3" type="submit">Apply</button>
	</form>

	<hr class="needs-js my-4">

	<h4 class="mb-2">Get start and end dates</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('Y-m-d', null, '\'%s\', \'%s\''); ?>);
echo 'Start: ', $DateRange-&gt;getStartDate()-&gt;format(DateTime::W3C), PHP_EOL,
	'End: ', $DateRange-&gt;getEndDate()-&gt;format(DateTime::RSS);</code></pre>
		</div>
		<!-- /.col -->
		<div class="col-md-4">
			<h5>Outputs</h5>

			<p>
				<?php
				echo 'Start: ', $demoDates->getStartDate()->format(DateTime::W3C),
					'<br>End: ', $demoDates->getEndDate()->format(DateTime::RSS);
				?>
			</p>
		</div>
		<!-- /.col -->
	</div>
	<!-- /.row -->

	<h4 class="mb-2">Iterate over date range</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('m/d/y', 'Y-m-d', '\'%s\', \'%s\''); ?>);

echo '&lt;ul&gt;';

foreach ($DateRange as $date) {
	echo '&lt;li&gt;', $date-&gt;format('M. jS Y'), '&lt;/li&gt;';
}

echo '&lt;/ul&gt;';</code></pre>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<h5>Output</h5>

			<?php
			$datesArray = $demoDates->toArray('M. jS Y');
			$days = (int) $demoDates->diff()->days;

			if ($days > SHOW_MAX_DATES) {
				$datesArray = array_slice($datesArray, 0, SHOW_MAX_DATES);
			}

			echo '<ul>';

			foreach ($datesArray as $date) {
				echo "<li>{$date}</li>";
			}

			echo '</ul>';

			if ($days > SHOW_MAX_DATES) {
				echo '<p class="text-secondary">* Output has been shortened in the demo.</p>';
			}
			?>
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row -->

	<h4 class="mb-2">Convert date range to array</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('U', null, '%s, \'%s\''); ?>);

print_r($DateRange-&gt;toArray('D, M j, Y'));</code></pre>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<h5>Output</h5>

			<?php
			$datesArray = $demoDates->toArray('D, M j, Y');
			$dateCount = count($datesArray);

			echo '<pre>',
			printSlice($datesArray),
			'</pre>';

			if ($dateCount > SHOW_MAX_DATES) {
				echo "<p class=\"text-secondary\">* Output has been shortened in the demo. The full date range contains
				{$dateCount} dates.</p>";
			}
			?>
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row -->

	<h4 class="mb-2">Compare dates</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('m/d/Y', null, '\'%s\', \'%s\''); ?>);
$now = new \DateTime('now', new \DateTimeZone('<?php echo $timezone->getName(); ?>'));
$comparison = $DateRange-&gt;compare($now);

if (DateRange::COMPARE_AFTER === $comparison) {
	/// $now is AFTER the date range
	echo 'The date range is in the past. 🕗';
}

if (DateRange::COMPARE_BEFORE === $comparison) {
	/// $now is BEFORE the date range
	echo 'The date range is in the future. 🚀';
}

if (DateRange::COMPARE_BETWEEN === $comparison) {
	/// $now is BETWEEN the start and end dates
	echo 'The date range includes the present. 🎁';
}</code></pre>
		</div>
		<!-- /.col -->
		<div class="col-md-4">
			<h5>Outputs</h5>

			<?php
			$comparison = $demoDates->compare($now);

			if (DateRange::COMPARE_AFTER === $comparison) {
				echo '<p>The date range is in the past. 🕗</p>';
			} else if (DateRange::COMPARE_BEFORE === $comparison) {
				echo '<p>The date range is in the future. 🚀</p>';
			} else if (DateRange::COMPARE_BETWEEN === $comparison) {
				echo '<p>The date range includes the present. 🎁</p>';
			}
			?>
		</div>
		<!-- /.col -->
	</div>
	<!-- /.row -->

	<h4>Excluding start or end dates</h4>

	<p class="mb-4">Use the class constants <code>DateRange::EXCLUDE_START_DATE</code> and
		<code>DateRange::EXCLUDE_START_DATE</code> to remove the first and/or last dates from the date range.</p>

	<h5>In comparisons:</h5>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('m.d.Y', null, '\'%s\', \'%s\''); ?>);

/// Exclude start date:

$comparison = $DateRange-&gt;compare(
	$DateRange-&gt;getStartDate(),
	DateRange::EXCLUDE_START_DATE
);

echo $defaultDates-&gt;getStartDate()-&gt;format('F jS, Y'), ' is';

if (DateRange::COMPARE_BETWEEN !== $comparison) {
	echo ' &lt;b&gt;NOT&lt;/b&gt; ';
}

echo 'in the date range.';

/// Exclude end date:

$anotherComparison = $DateRange-&gt;compare(
	$DateRange-&gt;getStartDate(),
	DateRange::EXCLUDE_END_DATE
);

echo $defaultDates-&gt;getEndDate()-&gt;format('F jS, Y'), ' is';

if (DateRange::COMPARE_BETWEEN !== $anotherComparison) {
	echo ' &lt;b&gt;NOT&lt;/b&gt; ';
}

echo 'in the date range.';
</code></pre>
		</div>
		<!-- /.col -->
		<div class="col-md-4">
			<h5>Outputs</h5>

			<?php
			/// Exclude start date:
			echo '<p>', $demoDates->getStartDate()->format('F jS, Y'), ' is';

			if (DateRange::COMPARE_BETWEEN !== $demoDates->compare($demoDates->getStartDate(), DateRange::EXCLUDE_START_DATE)) {
				echo ' <b>NOT</b> ';
			}

			echo 'in the date range.</p>';

			/// Exclude end date:
			echo '<p>', $demoDates->getEndDate()->format('F jS, Y'), ' is';

			if (DateRange::COMPARE_BETWEEN !== $demoDates->compare($demoDates->getEndDate(), DateRange::EXCLUDE_END_DATE)) {
				echo ' <b>NOT</b> ';
			}

			echo 'in the date range.</p>';
			?>
		</div>
		<!-- /.col -->
	</div>
	<!-- /.row -->

	<h5 class="mb-2">Exclude dates when iterating over date range:</h5>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('m-d-Y', null, '\'%s\', \'%s\''); ?>);

echo '&lt;ol&gt;';

/// Display date range without the start and end date
foreach ($DateRange-&gt;getDatePeriod($interval, DateRange::EXCLUDE_START_DATE | DateRange::EXCLUDE_END_DATE) as $date) {
	echo '&lt;li&gt;' . $date->format('M. jS Y') . '&lt;/li&gt;';
}

echo '&lt;ol&gt;';
</code></pre>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<h5>Outputs</h5>

			<?php
			echo '<ol>';

			$itr = SHOW_MAX_DATES;

			foreach ($demoDates->getDatePeriod($interval, DateRange::EXCLUDE_START_DATE | DateRange::EXCLUDE_END_DATE) as $date) {
				if ($itr < 0) {
					break;
				}

				if ($date instanceof DateTime) {
					echo '<li>' . $date->format('M. jS Y') . '</li>';
				}

				$itr--;
			}

			echo '</ol>';

			if ($itr < 0) {
				echo '<p class="text-secondary">* Output has been shortened in the demo.</p>';
			} else if ($itr === SHOW_MAX_DATES) {
				echo '<p class="text-secondary">(Nothing)</p>';
			}

			?>
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row mb-4 -->

	<h5 class="mb-2">Exclude dates when converting to array:</h5>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $demoDates->toString('m/d/Y', null, '\'%s\', \'%s\''); ?>);

$rangeArray = $DateRange-&gt;toArray(
	'D, M j, Y',
	false, /// Choose not to return the shortened array. Exclusions are not applied if this parameter is `true`
	new \DateInterval('<?php echo INTERVAL_SPEC; ?>'),
	DateRange::EXCLUDE_START_DATE | DateRange::EXCLUDE_END_DATE
);

print_r($rangeArray);
</code></pre>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<h5>Outputs</h5>

			<?php
			$datesArray = $demoDates->toArray('D, M j, Y', false, $interval, DateRange::EXCLUDE_START_DATE | DateRange::EXCLUDE_END_DATE);
			$dateCount = count($datesArray);

			if ($dateCount <= 0) {
				echo '<p class="text-secondary">(Nothing)</p>';
			} else {
				echo '<pre>',
				printSlice($datesArray),
				'</pre>';
			}

			if ($dateCount > SHOW_MAX_DATES) {
				echo "<p class=\"text-secondary\">* Output has been shortened in the demo. The full date range contains
				{$dateCount} dates.</p>";
			}
			?>
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row mb-4 -->

	<p class="my-4">More examples can be found in the repository&rsquo;s
		<a href="https://github.com/jasonjgardner/date-range/blob/master/README.md" rel="noopener">
			 <code>README.md</code>
		</a>
	</p>

	<hr>

	<p class="my-4 text-right"><a href="http://jasongardner.co" rel="noopener" target="_blank">By Jason</a></p>
</div>
<!-- /.container -->

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
		integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
		crossorigin="anonymous">
</script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
	(function ($, hljs) {
		$(function () {
			document.documentElement.classList.remove('no-js');

			$('#range').flatpickr({
				mode: 'range',
				altInput: true,
				shorthandCurrentMonth: true,
				defaultDate: $('[data-default-dates]').data('default-dates'),
				enableTime: true,
				appendTo: document.querySelector('.flatpickr'),
				'static': true
			});

			$('pre code').each(function(i, el) {
				hljs.highlightBlock(el);
			});
		})
	})(window.jQuery, window.hljs);
</script>
</body>
</html>
