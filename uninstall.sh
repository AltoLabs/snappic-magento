#!/bin/sh

echo "Removing Snappic Magento Extension from local instance..."

cd ../web/app

rm -rf code/community/AltoLabs \
       etc/modules/AltoLabs_Snappic.xml \
       design/frontend/base/default/layout/snappic.xml \
       design/frontend/base/default/template/snappic
