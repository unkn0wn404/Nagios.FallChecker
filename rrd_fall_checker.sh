#!/usr/bin/env bash
# usage rrd_fall_checker.sh path/to/file.rrd 10 20
# 0.5 - min ratio
# 2 - max ratio (can be empty)
FILE=$1

BASEPATH="/usr/local/www/cacti/rra/"
RRDRES=900
RRD="$BASEPATH$FILE"
#now
TIME=$(date +%s)-300
NOW=`rrdtool fetch $RRD AVERAGE -r $RRDRES \
   -e $(($TIME/$RRDRES*$RRDRES)) -s e-1h`
if [ "$?" != "0" ];then echo Fetch RRD data failed [$RRD]!; exit 3;fi;

#day ago
TIME=$(date +%s)-86400-300
YESTERDAY=`rrdtool fetch $RRD AVERAGE -r $RRDRES \
   -e $(($TIME/$RRDRES*$RRDRES)) -s e-1h`
if [ "$?" != "0" ];then echo Fetch RRD data failed [$RRD]!; exit 3;fi;

/usr/bin/env php "`dirname $0`/rrd_fall_checker.php" "$NOW" "$YESTERDAY" $2 $3