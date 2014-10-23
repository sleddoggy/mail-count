#!/bin/bash

# Enable EITHER the Postfix config OR the Exim4 config, depending on your
# mail server. Ensure the LOGFILE path is correct. Don't edit the REGEXP
# unless you know what you're doing :)

# Postfix config.

#LOGFILE='/var/log/mail.log'
#REGEXP='postfix/smtp.*status=(sent|bounced|deferred)'

# Exim4 config.

#LOGFILE='/var/log/exim4/mainlog'
#REGEXP='=>.*T=remote_smtp'

# Numbers of days to keep data.

DAYSKEEP=7

# ----------------------------------------------------

if [ ! -e "$LOGFILE" ]; then
	echo "Incorrect LOGFILE setting."
	exit
fi

WORKDIR=$(cd `dirname $0` && pwd)
CNTFILE="${WORKDIR}/counters"
STATFILE="${WORKDIR}/stats"
DAYSFILE="${WORKDIR}/daily"
STARTLINE=0
TODAY_CNT=0
NEWDAY=0

if [ -e "$CNTFILE" ]; then
	COUNTS=$(cat $CNTFILE)
fi
if [ -n "$COUNTS" ]; then
	STARTLINE=$(echo $COUNTS | awk '{print $1}')
	TODAY_CNT=$(echo $COUNTS | awk '{print $2}')
fi

LASTLINE=$(wc -l $LOGFILE | awk '{print $1}')

# Allow for rotation of the mail log.

if [ "$STARTLINE" -gt "$LASTLINE" ]; then
	STARTLINE=0
fi

# Start a new day?

if [ -e "$STATFILE" ]; then
	TODAY=$(date +'%d-%m-%Y')
	MTIME=$(stat -c %Y $STATFILE)
	LASTRUN=$(date +'%d-%m-%Y' --date="@$MTIME")
	if [ "$TODAY" != "$LASTRUN" ]; then
		NEWDAY=1
		mv $STATFILE ${STATFILE}-${LASTRUN}
		touch $STATFILE
		echo "$TODAY_CNT" >> ${STATFILE}-${LASTRUN}
		echo "$LASTRUN $TODAY_CNT" >> $DAYSFILE
		TODAY_CNT=0
		# Prune data to $DAYSKEEP days.
		KEEP=$(tail -n $DAYSKEEP $DAYSFILE)
		echo -e "$KEEP" > $DAYSFILE
		find $WORKDIR -type f -name stats-\* -mtime +$DAYSKEEP -exec rm {} \;
	fi
fi

if [ -e "$CNTFILE" ]; then
	INTERVAL_CNT=$(tail -n +${STARTLINE} $LOGFILE | egrep -c -e "$REGEXP")
	TODAY_CNT=$((TODAY_CNT + $INTERVAL_CNT))
fi
echo "$LASTLINE $TODAY_CNT" > $CNTFILE

STAMP=$(date +'%d %b %l:%M %p')
if [ ! -e "$STATFILE" ]; then
	echo -ne "$STAMP : --\n" > $STATFILE
else
	echo -ne "$STAMP : $INTERVAL_CNT\n" >> $STATFILE
fi

# Actions based on counters.

if [ -e "${WORKDIR}/actions" ]; then
	source ${WORKDIR}/actions
fi

exit 0

