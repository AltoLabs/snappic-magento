#!/bin/sh
RUN="./install.sh"

$RUN
while inotifywait -qre close_write .
do
  $RUN
done
