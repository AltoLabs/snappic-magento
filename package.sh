#!/bin/sh

./install.sh

cp packager.php ../web/shell/

echo "Packaging..."
cd ../web/shell
php packager.php --composer ../../Snappic/composer.json

echo "Saving package..."
mv ../var/connect/AltoLabs_Snappic*.tgz ../../Snappic

cd ../../Snappic
./uninstall.sh
