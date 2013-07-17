#!/usr/bin/env php
<?php
if (sizeof($argv) < 3)
	die("You must provide two parameters with rrd values and min level");

define("RES_OK",    0);
define("RES_WARN",  1);
define("RES_CRIT",  2);
define("RES_UNKNOWN",3);

$nowData = $argv[1];
$yesterdayData = $argv[2];
if (sizeof($argv) <= 5)
{
	$minCritLevel = (isset($argv[3])?(float)$argv[3]:null);
	$minWarnLevel = $minCritLevel;
	$maxCritLevel = (isset($argv[4])?(float)$argv[4]:null);
	$maxWarnLevel = $maxCritLevel;
}
else
{
	$minCritLevel = (isset($argv[3])?(float)$argv[3]:null);
	$minWarnLevel = (isset($argv[4])?(float)$argv[4]:null);
	$maxCritLevel = (isset($argv[5])?(float)$argv[5]:null);
	$maxWarnLevel = (isset($argv[6])?(float)$argv[6]:null);
}


echo "Check with min=$minWarnLevel..$minCritLevel, max=$maxWarnLevel..$maxCritLevel; ";
/**
 * Values are like
 *  RRD file name

 1358497800: 1.3100900000e+03
 1358498100: 1.3100900000e+03
 *
 * Output is like
 * Array
 (
     [1358498700] => 1310.09
     [1358499000] => 1310.09
     ...
     [1358502000] => 1426.2029
     [1358502300] => 1426.52
 )
 */
function parseFromString($data, $timeFormat = 'U')
{
	$return = array();
	if (preg_match_all('#([0-9]+):\s+([0-9\.e+-]+)#i',$data, $match))
	{
		for ($i = 0;$i < sizeof($match[1]);$i++)
		{
			$return[date($timeFormat, $match[1][$i])] = (float)$match[2][$i];
		}
	}
	return $return;
}

$now = parseFromString($nowData, 'H:i');
$yesterday = parseFromString($yesterdayData,'H:i');


$badPeriods = array();
$periodEquil = 0;
$continuosError = false;
$result = RES_UNKNOWN;
foreach ($now as $time=>$value)
{
	if (isset($yesterday[$time]))
	{
		$periodEquil++;
		if ($yesterday[$time] != 0)
			$ratio = number_format($value/$yesterday[$time],3);
		else
			$ratio = 0;

		if ($ratio < $minCritLevel)
		{
			$badPeriods[$time] = $ratio;
			$continuosError = true;
			$result = RES_CRIT;
		}
		elseif ($ratio < $minWarnLevel)
		{
			$badPeriods[$time] = $ratio;
			$continuosError = true;
			$result = RES_WARN;
		}
		elseif ($maxCritLevel && $ratio > $maxCritLevel)
		{
			$badPeriods[$time] = $ratio;
			$continuosError = true;
			$result = RES_CRIT;
		}
		elseif ($maxCritLevel && $ratio > $maxWarnLevel)
		{
			$badPeriods[$time] = $ratio;
			$continuosError = true;
			$result = RES_WARN;
		}
		else
		{
			$continuosError = false;
			$result = RES_OK;
		}
	}
}

//show results
if (!$periodEquil)
{
	echo "data for yesterday period does not exists or invalid";
	exit(RES_UNKNOWN);
}
if (sizeof($badPeriods))
{
	if ($continuosError)
	{
		//error persist at now
		echo "Bad ratio! (last is $ratio) ";
		foreach ($badPeriods as $time => $ratio)
			echo "[$time: $ratio];";

		exit($result);
	}
	else
	{
		//bad ratio was in past
		echo "Bad ratio was in past. Now is OK. (last is $ratio) ";
		foreach ($badPeriods as $time => $ratio)
			echo "[$time: $ratio];";

		exit($result);
	}
}
else
{
	echo "Ratio is ok. Last for $time is $ratio";
	exit(RES_OK);
}
