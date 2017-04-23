# install nginx
sudo apt-get -y install nginx
sudo /etc/init.d/nginx start

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