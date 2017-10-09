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
 * @param       $length Number of elements to print in total
 *
 * @return mixed|string String like `print_r()` output
 */
function printSlice(array $arr, $length)
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

$defaultDates = new DateRange('Sunday', 'Saturday', $defaultTimezone);

$interval = new DateInterval('P1D');
$timezone = new DateTimeZone($defaultTimezone);
$startDate = $defaultDates->getStartDate();
$endDate = $defaultDates->getEndDate();
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title>DateRange Demo</title>

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
		$defaultDates = new DateRange(explode(' to ', $_GET['range']), null, $timezone);
	}
} catch (Exception $e) {
	echo '<div class="alert alert-danger" role="alert">
		<h4 class="alert-heading">Invalid Date Range</h4>
		<p>The date range URL parameter cannot be parsed.</p>
		<p class="mb-0">' . $e->getMessage() . '</p></div></body></html>';
	exit;
}
?>

<div class="jumbotron bg-primary text-white">
	<div class="container">
		<div class="d-flex align-items-center justify-content-between">
			<h1 class="display-1">DateRange</h1>

			<a class="btn btn-lg bg-white text-primary" href="https://github.com/jasonjgardner/date-range" rel="noopener">
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
			echo $defaultDates->toString('Y-m-d H:i:s', null, '["%s", "%s"]'); ?>'>
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
$DateRange = new DateRange(<?php echo $defaultDates->toString('Y-m-d', null, '\'%s\', \'%s\''); ?>);
echo 'Start: ', $DateRange->getStartDate()->format(DateTime::W3C), PHP_EOL,
	'End: ', $DateRange->getEndDate()->format(DateTime::RSS);</code></pre>
		</div>
		<!-- /.col -->
		<div class="col-md-4">
			<h5>Outputs</h5>

			<p>
				<?php
				echo 'Start: ', $defaultDates->getStartDate()->format(DateTime::W3C),
					'<br>End: ', $defaultDates->getEndDate()->format(DateTime::RSS);
				?>
			</p>
		</div>
		<!-- /.col -->
	</div>
	<!-- /.row -->

	<h4 class="mb-2">Compare dates</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $defaultDates->toString('m/d/Y', null, '\'%s\', \'%s\''); ?>);
$now = new \DateTime('now', new \DateTimeZone('<?php echo $timezone->getName(); ?>'));
$comparison = $DateRange->compare($now);

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
			$comparison = $defaultDates->compare(new DateTime('now', $timezone));

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

	<h4 class="mb-2">Convert date range to array</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $defaultDates->toString('U', null, '%s, \'%s\''); ?>);

print_r($DateRange->toArray('D, M j, Y'));</code></pre>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<h5>Output</h5>

			<?php
			$datesArray = $defaultDates->toArray('D, M j, Y');
			$dateCount = count($datesArray);

			echo '<pre>',
			printSlice($datesArray, 5),
			'</pre>';

			if ($dateCount > 5) {
				echo "<p class=\"text-secondary\">* Output has been shortened in the demo. The full date range contains
				{$dateCount} dates.</p>";
			}
			?>
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row -->

	<h4 class="mb-2">Iterate over date range</h4>

	<div class="row mb-4">
		<div class="col-md-8">
			<pre class="rounded"><code class="php">use jasonjgardner\DateRange\DateRange;
$DateRange = new DateRange(<?php echo $defaultDates->toString('m/d/y', 'Y-m-d', '\'%s\', \'%s\''); ?>);

echo '&lt;ul&gt;';

foreach ($DateRange as $date) {
	echo '&lt;li&gt;', $date->format('M. jS Y'), '&lt;/li&gt;';
}

echo '&lt;/ul&gt;';</code></pre>
		</div>
		<!-- /.col-md-8 -->
		<div class="col-md-4">
			<h5>Output</h5>

			<?php
			$days = (int) $defaultDates->diff()->days;

			if ($days > 5) {
				$datesArray = array_slice($defaultDates->toArray('M. jS Y'), 0, 5);
			}

			echo '<ul>';

			foreach ($datesArray as $date) {
				echo "<li>{$date}</li>";
			}

			echo '</ul>';

			if ($days > 5) {
				echo '<p class="text-secondary">* Output has been shortened in the demo.</p>';
			}
			?>
		</div>
		<!-- /.col-md-4 -->
	</div>
	<!-- /.row -->

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
