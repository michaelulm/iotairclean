#!/bin/bash

echo "IoT AirClean Update started!";

# Update Current Python Scripts to get Data
rm iotairclean_subscriber.py
rm iotairclean_config.py
rm iotairclean_prediction.py
rm iotairclean_transfer.py
rm iotairclean_pushover.py
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_subscriber.py
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_config.py
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_prediction.py
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_transfer.py
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_pushover.py
chmod +x iotairclean_subscriber.py

echo "IoT AirClean Background Tasks updated!";

# Update Current ui

# create update directories
sudo mkdir /usr/share/nginx/html_update/css
sudo mkdir /usr/share/nginx/html_update/fonts
sudo mkdir /usr/share/nginx/html_update/js
sudo mkdir /usr/share/nginx/html_update/helpers
sudo mkdir /usr/share/nginx/html_update/img
# basic files
cd /usr/share/nginx/html_update/
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/iotairclean_station.php
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/iotairclean_visualization.php
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/index.php
# js, css and fonts
cd /usr/share/nginx/html_update/css
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap.css
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap.css.map
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap.min.css
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap.min.css.map
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap-theme.css
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap-theme.css.map
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap-theme.min.css
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/css/bootstrap-theme.min.css.map
cd /usr/share/nginx/html_update/fonts
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/fonts/glyphicons-halflings-regular.eot
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/fonts/glyphicons-halflings-regular.svg
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/fonts/glyphicons-halflings-regular.ttf
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/fonts/glyphicons-halflings-regular.woff
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/fonts/glyphicons-halflings-regular.woff2
cd /usr/share/nginx/html_update/js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/bootstrap.js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/bootstrap.min.js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/Chart.min.js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/jquery-3.2.1.min.js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/moment.min.js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/mqttws31.min.js
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/js/npm.js
cd /usr/share/nginx/html_update/img
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/img/iotairclean_logo_small.png
cd /usr/share/nginx/html_update/helpers
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/helpers/chartjs.php
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/helpers/database.php
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/helpers/iotairclean.php

# copy updated files
sudo cp -rf /usr/share/nginx/html_update/* /usr/share/nginx/html
# set files owner to nginx user
sudo chown -R root:root /usr/share/nginx/html
# remove all downloaded files
sudo rm -rf /usr/share/nginx/html_update/*

echo "IoT AirClean UI updated!"

# everything should be finished
echo "IoT AirClean Update finished!";
