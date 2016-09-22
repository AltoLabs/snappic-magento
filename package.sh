#!/bin/sh

./install.sh

echo "Packaging..."
cd ../web/shell
php packager.php --composer ../../Snappic/composer.json

echo "Saving package..."
mv ../var/connect/AltoLabs_Snappic-0.0.1-dev.tgz ../../Snappic

cd ../../Snappic
./uninstall.sh
