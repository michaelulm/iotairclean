#!/bin/bash

echo "IoT AirClean Update started!";

# Update Current Python Scripts to get Data
rm iotairclean_subscriber.py
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_subscriber.py
chmod +x iotairclean_subscriber.py

echo "IoT AirClean Background Tasks updated!";

# Update Current ui
cd /usr/share/nginx/html
sudo rm iot_airclean_station.php
sudo rm iotairclean_station.php
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/ui/iotairclean_station.php

echo "IoT AirClean UI updated!"

# everything should be finished
echo "IoT AirClean Update finished!";
