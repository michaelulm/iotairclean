#!/usr/bin/env python

# original author: Elisabeth Haberl, thanks for supporting the project

import sys
import airing
from pymongo import MongoClient
from datetime import datetime

# HANDLE parameters --------------------------------------
print '---------------------------------------------------'
print 'Number of arguments:', len(sys.argv), 'arguments.'
print 'Argument List:', str(sys.argv)

# default values
location    = 'Elisabeth'
station     = 'testraum@Elisabeth'
startTime   = datetime.now().replace(hour=0, minute=0, second=0, microsecond=0)
endTime     = datetime.now().replace(hour=23, minute=59, second=59, microsecond=0)
# values for detect airing
ppmFalling  = 400 # ppm
interval    = 30  # interval when data is gathered on arduino
timeslot    = 5   # 5 minutes will be compared to detect airing
maxListLen  = int((timeslot * 60) / interval)

# extract commandline arguments
if len(sys.argv) == 2 or len(sys.argv) == 4 or len(sys.argv) > 5:
    print '\r\r---------------------------------------------------'
    print "Need help? "
    print "Parameter 1: Location"
    print "Parameter 2: Station"
    print "Parameter 3: Start time in format %Y-%m-%d_%H:%M:%S e.g. 2017-12-01_12:05:00"
    print "Parameter 4: End time in format %Y-%m-%d_%H:%M:%S e.g. 2017-12-01_18:15:00"
    print '---------------------------------------------------\r\r'
    print "EXIT"
    sys.exit()
if len(sys.argv) == 3:
    location = sys.argv[1]
    station = sys.argv[2]
if len(sys.argv) == 5:
    location = sys.argv[1]
    station = sys.argv[2]
    startTime = datetime.strptime(sys.argv[3], "%Y-%m-%d_%H:%M:%S")
    endTime = datetime.strptime(sys.argv[4], "%Y-%m-%d_%H:%M:%S")

print 'INFO: location:  ', location
print 'INFO: station:   ', station
print 'INFO: startTime: ', startTime
print 'INFO: endTime:   ', endTime

print '---------------------------------------------------'

# HANDLE DB ----------------------------------------------
client = MongoClient()
db = client.iotairclean # choose database

# find all dataitems to compare for airing
data = db.measurements.find({
    'location':     location,
    'station':      station,
    'measured': {
        "$gt": startTime.strftime("%Y-%m-%d %H:%M:%S"),
        "$lt": endTime.strftime("%Y-%m-%d %H:%M:%S")
    }
})

if data.count() == 0:
    print 'INFO: No data available. EXIT'
    sys.exit()
else:
    print 'INFO: starting to process data ...'

airing.main(data, maxListLen, ppmFalling, db, location, station)
