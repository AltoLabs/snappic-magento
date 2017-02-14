#!/bin/sh

./uninstall.sh
./install.sh

while inotifywait -qre close_write .
do
  ./uninstall.sh
  ./install.sh
done
