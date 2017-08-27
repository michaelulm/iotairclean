import httplib, urllib
conn = httplib.HTTPConnection("www.iot-airclean.at")
conn.request("POST", "/api.pushover.php",
  urllib.urlencode({
    "user": "mike",
	"ppm": "100",
	"minutes": "5",
    "token": "IOTAIRCLEAN_TOKEN_NEEDED",
  }), { "Content-type": "application/x-www-form-urlencoded" })
response = conn.getresponse()
print response.status, response.reason
data = response.read()
print data