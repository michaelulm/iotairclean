# adds station configuration
import iotairclean_config_station
import time

def init():

	# import local private configuration (for each station, will not updated automatically)
	iotairclean_config_station.init()				# Call only once
	global settings
	global limits
	global pushDone
	global pushDoneShortTime
	global resetLimits
	global resetTimes
	settings = {}
	limits = {}
	pushDone = {}
	pushDoneShortTime = {}
	resetLimits = {}
	resetTimes = {}
	
	# global iot airclean configuration
	settings["station"] 					= iotairclean_config_station.private["stationname"]
	settings["transfer_to_iotairclean_at"] 	= iotairclean_config_station.private["transfer_to_iotairclean_at"]
	settings["air_fresh"] 	= 450
	
	# global current limits, will trigger push notification after reaching those limits
	limits[800] 	= False
	limits[1200] 	= False
	limits[1600] 	= False
	
	pushDone[800] 	= (time.time() - 1800)
	pushDone[1200] 	= (time.time() - 1800)
	pushDone[1600] 	= (time.time() - 1800)
	
	pushDoneShortTime[800] 		= (time.time() - 300)
	pushDoneShortTime[1200] 	= (time.time() - 300)
	pushDoneShortTime[1600] 	= (time.time() - 300)
	
	# global reset limits, to re-activate push notification for those limits
	resetLimits[800] 	= 600
	resetLimits[1200] 	= 700
	resetLimits[1600] 	= 750
	
	# global reset times, to prevent multiple pushes in short time
	resetTimes[800] 	= (time.time() - 300)
	resetTimes[1200] 	= (time.time() - 300)
	resetTimes[1600] 	= (time.time() - 300)
	
	
	# pushover mapping for iotairclean
	settings["user"]  = iotairclean_config_station.private["user"] 
	settings["token"] = iotairclean_config_station.private["token"] 