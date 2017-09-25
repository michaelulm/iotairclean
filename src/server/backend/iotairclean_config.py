# adds station configuration
import iotairclean_config_station

def init():

	# import local private configuration (for each station, will not updated automatically)
	iotairclean_config_station.init()				# Call only once
	global settings
	global limits
	global resetlimits
	settings = {}
	limits = {}
	resetlimits = {}
	
	# global iot airclean configuration
	settings["station"] 	= iotairclean_config_station.private["stationname"]
	settings["air_fresh"] 	= 450
	
	# global current limits, will trigger push notification after reaching those limits
	limits[800] 	= False
	limits[1200] 	= False
	limits[1600] 	= False
	
	# global reset limits, to re-activate push notification for those limits
	resetlimits[800] 	= 600
	resetlimits[1200] 	= 700
	resetlimits[1600] 	= 750
	
	
	# pushover mapping for iotairclean
	settings["user"]  = iotairclean_config_station.private["user"] 
	settings["token"] = iotairclean_config_station.private["token"] 