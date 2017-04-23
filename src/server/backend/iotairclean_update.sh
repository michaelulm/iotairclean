#!/bin/bash
rm iotairclean_updater.sh
wget https://raw.githubusercontent.com/michaelulm/iotairclean/master/src/server/backend/iotairclean_updater.sh
chmod +x iotairclean_updater.sh
./iotairclean_updater.sh
#sudo reboot
