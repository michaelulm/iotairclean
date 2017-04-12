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
         "measured": datetime.strptime(jmsg['measured'], "%Y-%m-%d %H:%M:%S"),
         "station": jmsg['station']
        })
    except:
      print('TODO Error Handling')

client = mqtt.Client()
client.on_connect = on_connect
client.on_message = on_message

#client.connect("127.0.0.1", 1883, 60)

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
           "measured": datetime.strptime(jmsg['measured'], "%Y-%m-%d %H:%M:%S"),
           "station": jmsg['station']
          })

    except:
      print 'TODO Error Handling' 
      print "Unexpected error:", sys.exc_info()[0]

