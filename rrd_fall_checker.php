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
$minLevel = (isset($argv[3])?(float)$argv[3]:null);
$maxLevel = (isset($argv[4])?(float)$argv[4]:null);

echo "Check with min=$minLevel, max=$maxLevel. ";
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
foreach ($now as $time=>$value)
{
	if (!empty($yesterday[$time]))
	{
		$periodEquil++;
		$ratio = number_format($value/$yesterday[$time],3);
		if ($minLevel && $minLevel > $ratio)
		{
			$badPeriods[$time] = $ratio;
			$continuosError = true;
		}
		elseif ($maxLevel && $maxLevel < $ratio)
		{
			$badPeriods[$time] = $ratio;
			$continuosError = true;
		}
		else
		{
			$continuosError = false;
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

		exit(RES_CRIT);
	}
	else
	{
		//bad ratio was in past
		echo "Bad ratio was in past. Now is OK. (last is $ratio) ";
		foreach ($badPeriods as $time => $ratio)
			echo "[$time: $ratio];";

		exit(RES_WARN);
	}
}
else
{
	echo "Ratio is ok. Last for $time is $ratio";
	exit(RES_OK);
}