#!/usr/bin/env python

import paho.mqtt.client as mqtt
import json
from pymongo import MongoClient
from datetime import datetime

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

client.connect("192.168.100.191", 1883, 60)

# Blocking call that processes network traffic, dispatches callbacks and
# handles reconnecting.
# Other loop*() functions are available that give a threaded interface and a
# manual interface.
client.loop_forever()
