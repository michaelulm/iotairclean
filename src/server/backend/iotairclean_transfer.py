# uses pushover for send a notifcation to a specific user

import httplib, urllib
import iotairclean_config

def transferdata(room, station, location, rpiserial, ppm, temperature, humidity):
	conn = httplib.HTTPConnection("www.iot-airclean.at")
	conn.request("POST", "/api.data.php",
	  urllib.urlencode({
		"user": iotairclean_config.settings["user"],
		"ppm": ppm,
		"temperature": temperature,
		"humidity": humidity,
		"room": room,
		"station": station,
		"location": location,
		"token": iotairclean_config.settings["token"],
		"rpiserial": rpiserial
	  }), { "Content-type": "application/x-www-form-urlencoded" })
		
	response = conn.getresponse()
	print response.status, response.reason
	data = response.read()
	print data