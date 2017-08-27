# adds station configuration
import iotairclean_config_station

def init():

	# import local private configuration (for each station, will not updated automatically)
	iotairclean_config_station.init()				# Call only once
	global settings
	global limits
	settings = {}
	limits = {}
	
	# global iot airclean configuration
	settings["station"] 	= iotairclean_config_station.private["stationname"]
	settings["air_fresh"] 	= 450
	
	# global current limits, will trigger push notification after reaching those limits
	limits[800] 	= 800
	limits[1200] 	= 1200
	limits[1600] 	= 1600
	
	# pushover mapping for iotairclean
	settings["user"]  = iotairclean_config_station.private["user"] 
	settings["token"] = iotairclean_config_station.private["token"] 