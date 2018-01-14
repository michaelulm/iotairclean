#!/usr/bin/env python

import paho.mqtt.client as mqtt						# MQTT send / receive messages
import json											# JSON encoding / decoding
import serial,sys,os
import socket										# socket connection for xbee communication
import time											# for current timestamp

import iotairclean_config							# adds global configuration
from iotairclean_pushover import pushover			# adds pushover method
from iotairclean_transfer import transferdata		# adds transfer to server method
from iotairclean_prediction import doCalcPrediction # adds prediction method for pre-calculation
from pymongo import MongoClient						# MongoDB client
from datetime import datetime						# DateTime Information 

# import global configuration
iotairclean_config.init()					# Call only once

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
		# store complete information to current database
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

# define connect and message method for handling mqtt messages
client = mqtt.Client(transport="websockets")
client.on_connect = on_connect
client.on_message = on_message
client.connect(socket.gethostbyname(socket.gethostname()), 1884, 60) 

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

# send a notifcation that subscriber already works	
pushover("IoT AirClean Station " + iotairclean_config.settings["station"] + " gestartet", '', 0, 0)
firstMsg = False

# for all new incoming we process every message the same way
while True:
	try:
		incoming = ser.readline().strip()
		print('XBee Received %s' %incoming)
		# check if incoming message is a json string, so we start handling the new message
		if is_json(incoming) == True:	
			# just send a pushover at the first message
			if firstMsg == False:
				pushover("IoT AirClean Station " + iotairclean_config.settings["station"] + " erste Daten empfangen", '', 0, 0)
				firstMsg = True
				
			# prepare and insert message to database
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
			# delegate incoming message to web ui and mqtt subscriber for handling the message
			client.publish("/iotairclean", incoming)
			# transfer to server if user allows transfer 
			if iotairclean_config.settings["transfer_to_iotairclean_at"] == True:
				print "IoT AirClean Transfer to Server now"
				transferdata(jmsg['room'], jmsg['station'], jmsg['location'], iotairclean_config.settings["serial"], jmsg['co2'], jmsg['t'], jmsg['h'] );
	
			# set notification message back to false
			for k, v in iotairclean_config.limits.items():
				print str(k) + " " + str(v)
				# only if already sent and resetLimits reached (fallen co2 value)
				if iotairclean_config.limits[k] == True and int(str(jmsg['co2'])) < int(str(iotairclean_config.resetLimits[k])):
					iotairclean_config.limits[k] = False
					#only push again after some time
					if resetTimes[k] < (time.time() - 300):
						resetTimes[k] = time.time()
						pushover("IoT AirClean Station " + iotairclean_config.settings["station"] + " unter " + str(iotairclean_config.resetLimits[k]) +" ppm CO2 gesunken", '', 0, 0)
			# notify user about successful complete Fresh Air
			if int(str(jmsg['co2'])) <= iotairclean_config.settings["air_fresh"]:
				pushover("IoT AirClean Station " + iotairclean_config.settings["station"] + " frische Luft :)", '', 0, 0)

			# just re-check current values (debug)
			for k, v in iotairclean_config.limits.items():
				print str(k) + " " + str(v)
				
			# starts prediction calculation
			doCalcPrediction(jmsg, client)
			
			#print("tmpValues for " + jmsg['room'] + " "+str(len(co2values)))
			#print("current value for " + jmsg['room'] + " "+str(jmsg['co2']))
	except:
		print 'TODO Error Handling' 
		print "Unexpected error 0:", sys.exc_info()[0]
		print "Unexpected error 1:", sys.exc_info()[1]
		print "Unexpected error 2:", sys.exc_info()[2]
		
		exc_type, exc_obj, exc_tb = sys.exc_info()
		fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
		print(exc_type, fname, exc_tb.tb_lineno)

