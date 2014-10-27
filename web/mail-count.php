<?php

// Data storage directory - no trailing slash.

$datadir = '/home/sleddog/mail-count';

// Cronjob interval, in minutes.

$interval = '15';

// Sort order for previous days, and single-day time entries.
// Set $latestfirst = true to have the most recent at top.

$latestfirst = false;

// ---------------------------------------------------------------------

$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$host = php_uname("n");
$file = basename(__FILE__);
$h1 = 'mail-count';

if (! is_dir($datadir)) {
	echo 'Incorrect $datadir setting.';
	exit;
}
if (! is_numeric($interval)) {
	echo 'Incorrect $interval setting.';
	exit;
}
if (! file_exists($datadir . '/counters') ) {
	echo 'No data.';
	exit;
}

if (isset($_GET['day'])) {
	if ( preg_match("/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/", $_GET['day']) || $_GET['day'] == 'today' ) {
		
		// Today's details.
		
		if ($_GET['day'] == 'today') {
			$dayfile = $datadir . '/stats';
			$timestamp = '<h2>Today:</h2>';
			$raw = file_get_contents($datadir . '/counters');
			list(, $day_ttl) = explode(' ', $raw);
			unset($raw);
			$day_ttl = "<tr><td class='label'>Total</td><td>" . trim($day_ttl) . "</td></tr>\n";
		}
		
		// Previous day details.
		
		else {
			$dayfile = $datadir . '/stats-' . $_GET['day'];
		}
		if ( file_exists($dayfile) ) {
			$interval_cnt = '';
			$raw = trim(file_get_contents($dayfile));
			$raw = str_replace('  ', ' ', $raw);
			$lines = explode("\n", $raw);
			unset($raw);
			
			if (empty($day_ttl)) {
				$last = array_pop($lines);
				$day_ttl = "<tr><td class='label'>Total</td><td>" . $last . "</td></tr>\n";
			}
			
			if ($latestfirst) {
				$lines = array_reverse($lines);
			}
			
			foreach ($lines as $line) {
				list($stamp, $ttl) = explode(' : ', $line);
				list(,,$timeonly,$apm) = explode(' ', $stamp);
				$interval_cnt .= "<tr><td>$timeonly $apm</td><td>$ttl</td></tr>\n";
			}
			
			$maildata = "<table class='day-total'>\n" . $day_ttl . "</table>\n<p>Details:</p>\n<table><tr><th class='label'>End Time</th><th class='label'>Mail Sent</th></tr>\n" . $interval_cnt . "</table>";
			list($dnum,$mnum,$year) = explode('-', $_GET['day']);
			if (empty($timestamp)) {
				$timestamp = '<h2>' . $months[$mnum] . ' ' . $dnum . ', ' . $year . ':</h2>';
			}
			$h1 = "<a href='$file'>$h1</a>";
			$nav = "<div class='nav'><a href='$file'>&larr;</a><a href='javascript:window.scrollTo(0,0)'>&uarr;</a></div>";
		}
	}
}

if (empty($maildata)) {
	
	$raw = trim(file_get_contents($datadir . '/stats'));
	$lines = explode("\n", $raw);
	$lines = array_reverse($lines);
	unset($raw);
	
	// Last interval.

	list(, $interval_ttl) = explode(' : ', $lines[0]);
	$interval_ttl = trim($interval_ttl);
	if ($interval < 60) {
		$interval_desc = $interval . ' min';
	}
	elseif ($interval == 60) {
		$interval_desc = 'hour';
	}
	else {
		$interval_desc = round(($interval / 60), 1) . ' hours';
	}
	$interval_ttl = "<tr><td class='label'>Last $interval_desc</td><td>$interval_ttl</td></tr>\n";

	// Last hour, if $interval <= 30.

	if ($interval <= 30) {
		$hour_ttl = 0;
		$perhour = round(60 / $interval);
		$i = 0;
		foreach ($lines as $line) {
			list(, $count) = explode(' : ', $line);
			$count = trim($count);
			if (is_numeric($count)) {
				$hour_ttl = ($hour_ttl + $count);
			}
			if ($i == $perhour) {
				break;
			}
			$i++;
		}
		$hour_ttl = "<tr><td class='label'>Last hour</td><td>${hour_ttl}</td></tr>\n";
	}

	// Today's total.

	$raw = trim(file_get_contents($datadir . '/counters'));
	list(, $today_ttl) = explode(' ', $raw);
	unset($raw);
	$today_ttl = "<tr><td class='label'><a href='?day=today'>Today</a></td><td>${today_ttl}</td></tr>\n";

	$today = "<h2>Today:</h2>\n<table>\n${interval_ttl}${hour_ttl}${today_ttl}</table>\n";

	// Previous days.

	$previous_days = "<h2>Previous Days:</h2>\n";
	if (file_exists($datadir . '/daily')) {
		$raw = trim(file_get_contents($datadir . '/daily'));
	}
	else {
		$previous_days .= '<p>No data for previous days.</p>';
	}
	if (!empty($raw)) {
		$previous_days .= "<table>\n<tr><th class='label'>Date</th><th class='label'>Total</th></tr>\n";
		$lines = explode("\n", $raw);
		if ($latestfirst) {
			$lines = array_reverse($lines);
		}
		foreach ($lines as $line) {
			list($stamp, $ttl) = explode(' ', $line);
			list($dnum,$mnum,$year) = explode('-', $stamp);
			$dispstamp = date("D, M j", mktime('0','1','0',$mnum,$dnum,$year));
			$previous_days .= "<tr><td><a href='?day=$stamp'>$dispstamp</a></td><td>$ttl</td></tr>\n";
		}
		$previous_days .= '</table>';
	}

	$maildata = $today . $previous_days;
	$timestamp = '<p>Updated: ' . date("g:i A, M j", filemtime($datadir . '/counters') ) . '</p>';
	$nav = '';
}
?>
<!DOCTYPE html>
<html>
	
<head>
<meta charset="UTF-8">
<title>mail-count : <?php echo $host ?></title>
<link rel="stylesheet" href="mail-count.css" type="text/css" />
</head>

<body>
<div class="page">

<h1><?php echo $h1 ?></h1>
<div class="desc">OUTGOING SMTP DELIVERIES</div>

<?php echo $timestamp ?>

<?php echo $maildata ?>

<?php echo $nav ?>

</div>
</body>
</html>
