#!/usr/bin/env python

import paho.mqtt.client as mqtt
import json
import serial
import sys
from pymongo import MongoClient
from datetime import datetime

# init serial connection
# currently with usb
ser = serial.Serial('/dev/ttyUSB0', 9600, timeout=.5)

# init MongoDB Client
client = MongoClient()
db = client.iotairclean #connect to iotairclean database

# The callback for when the client receives a CONNACK response from the server.
def on_connect(client, userdata, flags, rc):
    print("Connected with result code "+str(rc))

    # Subscribing in on_connect() means that if we lose the connection and
    # reconnect then subscriptions will be renewed.
    client.subscribe("iotairclean/#")

# The callback for when a PUBLISH message is received from the server.
def on_message(client, userdata, msg):
    try:
      print(msg.topic+" "+str(msg.payload))
      jmsg = json.loads(msg.payload)
      result = db.measurements.insert_one(
        {
         "temperature": jmsg['t'], 
         "humidity": jmsg['h'], 
         "co2": jmsg['co2'], 
         "measured": datetime.now().strftime ( "%Y-%m-%d %H:%M:%S"),
         "station": jmsg['station']
        })
    except:
      print('TODO Error Handling')

client = mqtt.Client(transport="websockets")
client.on_connect = on_connect
client.on_message = on_message

client.connect("192.168.100.191", 1884, 60) 

# Blocking call that processes network traffic, dispatches callbacks and
# handles reconnecting.
# Other loop*() functions are available that give a threaded interface and a
# manual interface.

#client.loop_forever()

def is_json(myjson):
    try:
      json_object = json.loads(myjson)
    except ValueError, e:
      return False
    return True

	
# stores incoming co2 values and will need for comparing co2 values later
co2list = {}
co2values = []
intervalTime = 30


# simple calculation of rate of growth
def calcRateOfGrowth(presentValue, pastValue, nrOfValues):
    print "present " + str(presentValue) + ", past " + str(pastValue) + ", nrOfValues " + str(nrOfValues)
    output = float(float(presentValue / pastValue) ** float(float(1)/ float(nrOfValues))) - 1
    return output
  
def doCalcPrediction(jmsg):
    tmpValue = 0
	#prepare empty list, this should get bigger every time
    co2values = []
	# if there are no values, we will add a new later, otherwise we will add values to existing values 
    for k, v in co2list.iteritems():
	  #print k, v
      if k == jmsg['room']:
        co2values = v
    co2values.append(jmsg['co2'])
    co2list[jmsg['room']] = co2values 
    # get first and last value for rateOfGrowth Calculation over all items
    firstValue = co2values[0]
    lastValue = jmsg['co2']

    rateOfGrowthOverall = calcRateOfGrowth(lastValue, firstValue, len(co2values))
    print "nr of values for " + jmsg['room'] + " " + str(len(co2values))
    print rateOfGrowthOverall
	
    # we will get the last 5 minute for our calculation, if they exists
    nrOfItemsNeeded = 10
    if nrOfItemsNeeded > len(co2values):
      nrOfItemsNeeded = len(co2values)
    co2predictionBase = co2values[-nrOfItemsNeeded:]
    print "predictionBase has " + str(len(co2predictionBase)) + " values"

    # we are doing a much longer than 5 minute prediction calculation,
    # but we only use the last 5 minutes and re-use the new calcuated values for a possible increase statistic
    count = 0
    # list for complete prediction calculation for about an hour for later usage 
    co2predictionCalcOverall = []
    while (count < 12):
      lastBaseCalc = 0
      # now we doing calculation for the prediction
      co2predictionCalc = []
      for value in co2predictionBase:
        #print "BASE co2 value: " + str(value)
    	# temp store last Base Calc for difference
        lastBaseCalc = value
    	# calculate new prediction
        value = value * (rateOfGrowthOverall + 1)
        co2predictionCalc.append(value)
        co2predictionCalcOverall.append(value)
    
      # now get diff of lastBaseCalc and first prediction calc to get a better forecast line (reduce hugh differences in forecalc)
      diffCalc = lastBaseCalc - co2predictionCalc[0]
	
      # now we will use the new values in the next loop
      co2predictionBase = []
      predictionNr = 0
      for value in co2predictionCalc:
        #print "PREDICTION Minute " + str(predictionNr / 2) + ", nr " + str(predictionNr) + ", co2 value: " + str(value)
        predictionNr = predictionNr + 1
        co2predictionBase.append(value + diffCalc)
    	
      count = count + 1

    print "#### complete overall prediction calculation for " + str(len(co2predictionCalcOverall)) + " values, about " + str(len(co2predictionCalcOverall)/2) + " Minutes ####"
    predictionNr = 0
    for value in co2predictionCalcOverall:
      print "PREDICTION Minute " + str(predictionNr / 2) + ", nr " + str(predictionNr) + ", co2 value: " + str(value)
      predictionNr = predictionNr + 1	
      jmsg['counter'] = predictionNr
      jmsg['co2'] = value
      client.publish("/iotairclean", json.dumps(jmsg))

while True:
    try:
      incoming = ser.readline().strip()
      print('XBee Received %s' %incoming)
      if is_json(incoming) == True:      
        jmsg = json.loads(str(incoming))
        result = db.measurements.insert_one(
          {
           "temperature": jmsg['t'],
           "humidity": jmsg['h'],
           "co2": jmsg['co2'],
           "measured": datetime.now().strftime ( "%Y-%m-%d %H:%M:%S"),
           "station": jmsg['station'],
           "location": jmsg['location'],
           "room": jmsg['room']
          })
        client.publish("/iotairclean", incoming)
        
		
        doCalcPrediction(jmsg)
		
		
        print("tmpValues for " + jmsg['room'] + " "+str(len(co2values)))
        print("current value for " + jmsg['room'] + " "+str(jmsg['co2']))
    except:
      print 'TODO Error Handling' 
      print "Unexpected error 0:", sys.exc_info()[0]
      print "Unexpected error 1:", sys.exc_info()[1]
      print "Unexpected error 2:", sys.exc_info()[2]

