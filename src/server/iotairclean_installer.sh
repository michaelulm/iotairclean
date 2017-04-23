# install nginx
sudo apt-get -y install nginx
sudo /etc/init.d/nginx start

# prepare / modify standard html intstallation
sudo mv /var/www/html/ /var/www/html_orig/
sudo ln -s /var/www/html /usr/share/nginx/html
cd /etc/nginx/sites-available
sudo mv default default_orig
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/config/default


# install php 
sudo apt-get -y install php5-fpm
sudo /etc/init.d/nginx reload

# install mongodb
sudo apt-get -y install mongodb-server
sudo apt-get -y install php5-dev
sudo apt-get -y install php-pear
sudo pecl install mongo
sudo "extension=mongo.so" >> /etc/php5/fpm/php.ini
sudo killall php5-fpm
sudo /etc/init.d/php5-fpm restart

# install ntp 
sudo apt-get -y install ntpdate

# download current update script
cd /home/pi
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_update.sh
chmod +x iotairclean_update.sh
./iotairclean_update.sh