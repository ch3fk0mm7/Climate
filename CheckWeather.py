import serial
import time
import datetime
import signal
import urllib.request
import mysql.connector
from decimal import Decimal

### Start Reading OpenWeatherMap ###

def url_builder(city_id):                            # Search for your city ID here: http://bulk.openweathermap.org/sample/city.list.json.gz
    user_api = '71a86e69f963f1903e8722bab74d102e'   # Obtain yours form: http://openweathermap.org/
    unit = 'metric'                                 # For Fahrenheit use imperial, for Celsius use metric, and the default is Kelvin.
    api = 'http://api.openweathermap.org/data/2.5/weather?id='

    full_api_url = api + str(city_id) + '&mode=json&units=' + unit + '&APPID=' + user_api
    return full_api_url

with urllib.request.urlopen(url_builder(2878074)) as Weather:
        html = Weather.read().decode("utf-8")

tempOutdoor = html[html.find('temp')+6:html.find(',', html.find('temp'))]
humidityOutdoor = html[html.find('humidity')+10:html.find(',', html.find('humidity'))]

### End Reading OpenWeatherMap ###


### START - Read Last SQL Entry ###

db = mysql.connector.connect(user='phpmyadmin', password='LifCenter', host='localhost', database='phpmyadmin')
cursor = db.cursor()
Querry ="SELECT * FROM Climate ORDER BY Timestamp DESC LIMIT 1"
cursor.execute(Querry)
PreviousData = cursor.fetchall()
# PreTimeStamp = PreviousData[0][0]
# PreTmpSensor = PreviousData[0][1]
# PreHumSensor = PreviousData[0][2]
# PreTmpOutsid = PreviousData[0][3]
# PreHumOutsid = PreviousData[0][4]


### FINISH - Read Last SQL Entry ###



### Start Reading Arduino ###

ser=serial.Serial('/dev/ttyUSB0',9600) #Was ttyACM0 when using Arduino Uno

Cnt = 0
while True:
	signal.alarm(3)
	read_ser=str(ser.readline())
	if read_ser:
		### Connect to MySQL
		db = mysql.connector.connect(user='phpmyadmin', password='LifCenter', host='localhost', database='phpmyadmin')
		cursor = db.cursor()
		Querry ="INSERT INTO Climate (Temp_Sensor, Humid_Sensor, Temp_Outside, Humid_Outside) VALUES (%s, %s, %s, %s)"
		#Cut the String received via Serial connection into the right pieces
		#e.g. "49.80,16.10"

		DelimiterAtPos = read_ser.find(",")
		helpStr = ""
		for i in range(2, DelimiterAtPos):
			helpStr = helpStr + read_ser[i]

		HumidityFromSensor = helpStr
		helpStr = ""

		for i in range(DelimiterAtPos + 1, len(read_ser)-5):
			helpStr=helpStr + read_ser[i]

		TempFromSensor = helpStr
		
		Querry_Data = (TempFromSensor, HumidityFromSensor, tempOutdoor, humidityOutdoor)
		#print ("Previous Data: ", PreviousData)
		#print (Querry % Querry_Data)
		
		if (Decimal(TempFromSensor) != Decimal(PreviousData[0][1]) or Decimal(HumidityFromSensor) != Decimal(PreviousData[0][2]) or Decimal(tempOutdoor) != Decimal(PreviousData[0][3]) or Decimal(humidityOutdoor) != Decimal(PreviousData[0][4])):
		
			#print ("Entering New Record")
			cursor.execute(Querry,Querry_Data)
			db.commit()
			cursor.close()
			db.close()
		else:
			#print ("No Changes with Last Record")
			cursor.close()
			db.close()
		
	signal.alarm(0)
	Cnt = Cnt + 1
	


