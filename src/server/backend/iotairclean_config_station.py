# this is an individual station configuration

def init():

	global private
	private = {}
	
	# pushover mapping for iotairclean, please contact provider of www.iot-airclean.at for more details
	private["user"] 		= "USER"
	private["token"] 		= "IOTAIRCLEAN_TOKEN_NEEDED"
	
	# activate server transfer, optin, default = false => NO Transfer to www.iot-airclean.at
	private["transfer_to_iotairclean_at"] = False	# add a leading # to activate
	#private["transfer_to_iotairclean_at"] = True	# remove leading # to activate
	
	# local station config
	private["stationname"] 	= "STATION_NAME"		
	#private["stationname"] 	= "myHomeAirClean"		# example
	