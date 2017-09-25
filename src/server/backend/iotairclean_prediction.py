# Prediction methods for pre-calculation trend of co2  values

import json
import iotairclean_config
from iotairclean_pushover import pushover

# stores incoming co2 values and will need for comparing co2 values later
co2list = {}
co2values = []
intervalTime = 30

# simple calculation of rate of growth
def calcRateOfGrowth(presentValue, pastValue, nrOfValues):
	print "present " + str(presentValue) + ", past " + str(pastValue) + ", nrOfValues " + str(nrOfValues)
	output = float(float(presentValue / pastValue) ** float(float(1)/ float(nrOfValues))) - 1
	return output
  
def doCalcPrediction(jmsg, client):
	co2orig = jmsg['co2'];
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
		
		# set notification message back to false
		for k, v in iotairclean_config.limits.items():
			if int(float(str(value))) > int(k) and v == False:
				iotairclean_config.limits[k] = True
				# send a push notification to do airing in a few minutes
				pushover("", str(jmsg['room']), int(float(str(co2orig))), int(float(str(predictionNr / 2))) )
		
		predictionNr = predictionNr + 1	
		jmsg['counter'] = predictionNr
		jmsg['co2'] = value
		client.publish("/iotairclean", json.dumps(jmsg))
		
		#for k, v in iotairclean_config.limits.items():
		#	print k
