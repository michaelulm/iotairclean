# uses pushover for send a notifcation to a specific user

import httplib, urllib
import iotairclean_config

def pushover(msg, room, ppm, minutes):
	conn = httplib.HTTPConnection("www.iot-airclean.at")
	# if there is no room definied, we send the given message
	if room == '':
		conn.request("POST", "/api.pushover.php",
		  urllib.urlencode({
			"user": iotairclean_config.settings["user"],
			"msg": msg,
			"token": iotairclean_config.settings["token"],
		  }), { "Content-type": "application/x-www-form-urlencoded" })

	# otherwise we will send airing information to current user
	else:
		conn.request("POST", "/api.pushover.php",
		  urllib.urlencode({
			"user": iotairclean_config.settings["user"],
			"ppm": ppm,
			"room": room,
			"minutes": minutes,
			"token": iotairclean_config.settings["token"],
		  }), { "Content-type": "application/x-www-form-urlencoded" })
		
	response = conn.getresponse()
	print response.status, response.reason
	data = response.read()
	print data