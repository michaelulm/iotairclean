#!/bin/bash

### IoT AirClean Basis Installation Script ###

# install nginx
sudo apt-get -y install nginx
sudo /etc/init.d/nginx start
sudo apt-get -y install python-pip

# prepare / modify standard html intstallation
sudo mv /var/www/html/ /var/www/html_orig/
sudo ln -s /var/www/html /usr/share/nginx/html
cd /etc/nginx/sites-available
sudo mv default default_orig
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/config/default
sudo /etc/init.d/nginx restart

# install php 
sudo apt-get -y install php5-fpm
sudo /etc/init.d/nginx reload

# install mongodb
sudo apt-get -y install mongodb-server
sudo apt-get -y install php5-dev
sudo apt-get -y install php-pear
sudo pecl install mongo
sudo cp /etc/php5/fpm/php.ini /etc/php5/fpm/php.ini_orig
sudo rm /etc/php5/fpm/php.ini
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/config/php.ini
sudo killall php5-fpm
sudo /etc/init.d/php5-fpm restart
sudo pip install pymongo

# install mqtt
sudo pip install paho-mqtt
 
# install xbee
sudo apt-get -y install python-serial

# install ntp 
sudo apt-get -y install ntpdate

(crontab -l 2>/dev/null; echo "0 7 * * 1       sudo apt-get -y update && sudo apt-get -y upgrade") | crontab -
#(crontab -l 2>/dev/null; echo "0 7 * * 1       /home/pi/certbot-auto renew") | crontab -
(crontab -l 2>/dev/null; echo "0 21 * * *      /home/pi/iotairclean_update.sh") | crontab -

# download current update script
cd /home/pi
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_update.sh
chmod +x iotairclean_update.sh
./iotairclean_update.sh