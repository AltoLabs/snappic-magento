#!/bin/bash

./install.sh

echo "Installing required things..."
cp shell/*.php ../web/shell/

cd ../web/shell

echo "Obfuscating..."

echo "  -> controllers..."
files=(CartController IndexController InventoryController)
for var in "${files[@]}"; do
  php obfuscate.php "../app/code/community/AltoLabs/Snappic/controllers/${var}.php"
done

echo "  -> migrations..."
files=(data-install-0.0.1 data-upgrade-0.0.1-0.0.2 data-upgrade-0.0.2-0.0.3 data-upgrade-0.0.3-0.0.4)
for var in "${files[@]}"; do
  php obfuscate.php "../app/code/community/AltoLabs/Snappic/data/snappic_setup/${var}.php"
done

echo "  -> helpers..."
files=(Data)
for var in "${files[@]}"; do
  php obfuscate.php "../app/code/community/AltoLabs/Snappic/Helper/${var}.php"
done

echo "  -> models..."
files=(Connect Observer Api2/Snappic/Product Api2/Snappic/Store Api2/Snappic/Product/Rest/Admin/V1 Api2/Snappic/Store/Rest/Admin/V1)
for var in "${files[@]}"; do
  php obfuscate.php "../app/code/community/AltoLabs/Snappic/Model/${var}.php"
done

php packager.php --composer ../../Snappic/composer.json

echo "Moving package..."
mv ../var/connect/AltoLabs_Snappic*.tgz ../../Snappic
