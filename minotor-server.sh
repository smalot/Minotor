#!/bin/sh
### BEGIN INIT INFO
# Provides:          minotor-server.sh
# Required-Start:    $local_fs $network $named $time $syslog
# Required-Stop:     $local_fs $network $named $time $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Description:       <DESCRIPTION>
### END INIT INFO

BIN="/usr/bin/php"
FOLDER="/data/www/minotor"
RUNAS="root"

PIDFILE=/var/run/minotor-server.pid
LOGFILE=/var/log/minotor-server.log

start() {
  echo 'Starting service…' >&2
  local CMD="$BIN console server:start -p \"$PIDFILE\" >> \"$LOGFILE\" 1>&2 &"
  cd $FOLDER
  su -c "$CMD" $RUNAS
  echo 'Service started' >&2
}

stop() {
  echo 'Stopping service…' >&2
  local CMD="$BIN console server:stop -p \"$PIDFILE\""
  cd $FOLDER
  su -c "$CMD" $RUNAS
  echo 'Service stopped' >&2
}

uninstall() {
  echo -n "Are you really sure you want to uninstall this service? That cannot be undone. [yes|No] "
  local SURE
  read SURE
  if [ "$SURE" = "yes" ]; then
    stop
    rm -f "$PIDFILE"
    echo "Notice: log file is not be removed: '$LOGFILE'" >&2
    update-rc.d -f minotor remove
    rm -fv "$0"
  fi
}

status() {
  local CMD="$BIN console server:status -p \"$PIDFILE\""
  cd $FOLDER
  su -c "$CMD" $RUNAS
}

case "$1" in
  start)
    start
    ;;
  stop)
    stop
    ;;
  uninstall)
    uninstall
    ;;
  restart)
    stop
    start
    ;;
  status)
    status
    ;;
  *)
    echo "Usage: $0 {start|stop|restart|status|uninstall}"
esac
