
import sys


  
timeInterval = 30

# simple calculation of rate of growth
def calcRateOfGrowth(presentValue, pastValue, nrOfValues):
  print "present " + str(presentValue) + ", past " + str(pastValue) + ", nrOfValues " + str(nrOfValues)
  output = float(float(presentValue / pastValue) ** float(float(1)/ float(nrOfValues))) - 1
  return output

co2values = [457,457,460,460,458,463,465,465,466,469,464,466,464,463,462,468,465,463,461,464,461,460,465,465,459,460,460,461,462,459,462,458,461,463,462,464,461,470,472,480,478,466,467,468,467,467,471,506,495,495,493,488,479,478,479,483,480,479,481,484,484,487,490,489,490,491,490,493,496,504,503,503,506,513,509,506,507,514,519,518,525,521,526,525,525,524,520,525,527,526,529,531,537,540,542,537,537,537,544,544,551,552,546,546,548,552,553,554,557,556,553,551,548,552,572,592,599,597,587,587,588,590,588,587,593,595,592,592,601,606,614,618,619,625,653,631,633,624,613,622,617,625,631,634,637,635,642,629,636,642,641,647,644,643,641,644,647,646,646,645,657,662,650,681,724,710,692]



# small list
#co2values = [457,460,469,500]

tmpValue = 0
# get first and last value for rateOfGrowth Calculation over all items
firstValue = co2values[0]
# list for all rateOfGrowth between neighbour values
rateOfGrowthList = []

for value in co2values:
  # we need to parse int to float value for later calculation, otherwise we will get 0 or 1 ... and that's not so useful
  value = float(value)
  if tmpValue > 0:
    rateOfGrowth = calcRateOfGrowth(value, tmpValue, 2)
    rateOfGrowthList.append(rateOfGrowth)
    print rateOfGrowth
	
  tmpValue = value
  lastValue = value
  print value

print "overall rates of Growth " + str(len(rateOfGrowthList))
#for rate in rateOfGrowthList:
#  print rate
rateOfGrowthOverall = calcRateOfGrowth(lastValue, firstValue, len(rateOfGrowthList))
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
  predictionNr = predictionNr + 1


  
  
	