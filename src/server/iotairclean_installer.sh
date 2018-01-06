#!/bin/bash

### IoT AirClean Basis Installation Script ###

# update raspberry, to improve installation for "older" raspberry installation
sudo apt-get update
sudo apt-get dist-upgrade -y
sudo apt-get upgrade -y
sudo apt-get install unattended-upgrades -y

# install nginx
sudo apt-get install -t stretch nginx -y
sudo /etc/init.d/nginx start
sudo apt-get install python-pip -y
sudo pip install pip --upgrade

# prepare / modify standard html intstallation
sudo mv /var/www/html/ /var/www/html_orig/
sudo ln -s /usr/share/nginx/html /var/www/html
sudo mkdir /usr/share/nginx/html
sudo mkdir /usr/share/nginx/html_update/
cd /etc/nginx/sites-available
sudo mv default default_orig
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/config/default
sudo /etc/init.d/nginx restart

# install php 
sudo apt-get install -t stretch php7.0 php7.0-curl php7.0-gd php7.0-fpm php7.0-cli php7.0-opcache php7.0-mbstring php7.0-xml php7.0-zip php7.0-dev -y
sudo /etc/init.d/nginx reload

# install mongodb
sudo apt-get install mongodb-server -y
sudo apt-get install -t stretch php-pear -y
sudo pecl channel-update pecl.php.net
sudo pecl install mongodb
sudo cp /etc/php/7.0/fpm/php.ini /etc/php/7.0/fpm/php.ini_orig
sudo rm /etc/php/7.0/fpm/php.ini
cd /etc/php/7.0/fpm/
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/config/php.ini
sudo killall php7.0-fpm
sudo /etc/init.d/php7.0-fpm restart
sudo pip install pymongo

# install mosquitto
sudo apt-get install mosquitto mosquitto-clients -y
sudo mv /etc/mosquitto/mosquitto.conf /etc/mosquitto/mosquitto.conf_orig
cd /etc/mosquitto/
sudo wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/config/mosquitto.conf
sudo /etc/init.d/mosquitto restart

# install mqtt
sudo pip install paho-mqtt==1.2.3
 
# install xbee
sudo apt-get install python-serial -y

# install ntp 
sudo apt-get install ntpdate -y

(crontab -l 2>/dev/null; echo "0 7 * * 1       sudo apt-get -y update && sudo apt-get -y upgrade") | crontab -
#(crontab -l 2>/dev/null; echo "0 7 * * 1       /home/pi/certbot-auto renew") | crontab -
(crontab -l 2>/dev/null; echo "0 21 * * *      /home/pi/iotairclean/iotairclean_update.sh") | crontab -

# config auto start for iotairclean subscriber
sudo cp /etc/rc.local /etc/rc.local.tmp
sudo rm -f /etc/rc.local
sudo sed '$ d' /etc/rc.local.tmp > /home/pi/iotairclean/rc.local
sudo rm -f /etc/rc.local.tmp
sudo echo "/bin/sleep 30 && /etc/init.d/mosquitto restart &" >> /home/pi/iotairclean/rc.local
sudo echo "/bin/sleep 45 && /home/pi/iotairclean/iotairclean_subscriber.py &" >> /home/pi/iotairclean/rc.local
sudo echo "exit 0" >> /home/pi/iotairclean/rc.local
sudo cp /home/pi/iotairclean/rc.local /etc/rc.local
sudo chmod 0755 /etc/rc.local

# download current update script
cd /home/pi
mkdir iotairclean
cd iotairclean
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_update.sh
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_config_station.py
chmod +x iotairclean_update.sh
./iotairclean_update.sh