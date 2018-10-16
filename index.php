<?php

require 'vendor/autoload.php';

date_default_timezone_set('America/Toronto');

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

// Important for .aws credentials
putenv("HOME=/var/www/");

print $home;
$sdk = new Aws\Sdk([
     'region' => 'us-east-1',
     'version' => 'latest'
     ]);

$dynamodb = $sdk->createDynamoDb();
$marshaler = new Marshaler();

$tableName = 'TempMeas';

$date = new DateTime();
$tsStart = $date->getTimestamp()-(60*60);

$eav_data[':timestamp'] = $tsStart;
$eav_data[':sensorid'] = "240";
$eav_data = array_filter($eav_data);
$eav_json = json_encode($eav_data, JSON_PRETTY_PRINT);

$params = [
	'TableName' => $tableName,
	'KeyConditionExpression' =>
				 '#sid = :sensorid AND #ts > :timestamp',
	'ExpressionAttributeNames'=> ['#ts' => 'Timestamp', '#sid' => 'SensorID'],
	'ExpressionAttributeValues'=>$marshaler->marshalJson($eav_json),
	];

//echo "Querying .... \n";

try {
    $result = $dynamodb->query($params);

    //echo "Query succeeded.\n";
    // Convert for googleChart
    $cntr = 0;
    $chartTemp = [];
    $chartHum  = [];
    $chartBat  = [];
    
    foreach ($result['Items'] as $i) {
        $readings = $marshaler->unmarshalItem($i);
        //array_push($chartTemp, arrray("timestamp" => $readings['Timestamp'], "temperature" => $readings['Temperature']));
        $chartTemp[$cntr] = array($readings['Timestamp'],$readings['Temperature']/100);
        $chartHum[$cntr]  = array($readings['Timestamp'],$readings['Humidity']/100);
        $chartBat[$cntr]  = array($readings['Timestamp'],$readings['BatteryLevel']/1000);

        if(0) {
            print $cntr . ": " . $readings['Timestamp'] . ";T=" . $readings['Temperature'] . ";H=" . $readings['Humidity'] . ";A=".$readings['AlarmLevel'] . "\n";
            //print $readings['Temperature'] . ': ' . $readings['Humidity'] . " ... \n";
        }

        // Keep count for googleChart
        $cntr++;
    }

} catch (DynamoDbException $e) {
    echo "Unable to query :\n";
    echo $e->getMessage() . "\n";

}

//function drawChart() {
//    var data = google.visualization.arrayToDataTable([
//        ['Year', 'Sales', 'Expenses'],
//        ['2013',  1000,      400],
//        ['2014',  1170,      460],
//        ['2015',  660,       1120],
//        ['2016',  1030,      540]
//    ]);
//    


?>


<html>
  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawTempChart);
      google.charts.setOnLoadCallback(drawHumidChart);
      google.charts.setOnLoadCallback(drawBatteryChart);

      <!-- TEMP CHART-->
      function drawTempChart() {
      var data = google.visualization.arrayToDataTable([
      ['Time', 'Basement'],
      <?php
      for($i=0;$i<$cntr;$i++){
          echo "['" . date("G:i:s",$chartTemp[$i][0]) . "'," . $chartTemp[$i][1] . "]";
          //echo "['" .$chartTemp[$i][0] . "'," . $chartTemp[$i][1] . "]";
          if($i < $cntr-1) {
              echo ",\n";
          }
      }
      ?>
         ]);
         
         var options = {
         title: 'Temperature',
         hAxis: {title: 'Time',  titleTextStyle: {color: '#333'}},
         vAxis: {minValue: 20}
         };

         var chart = new google.visualization.AreaChart(document.getElementById('tempchart_div'));
         chart.draw(data, options);
      }

      <!-- HUMIDITY CHART-->              
      function drawHumidChart() {
      var data = google.visualization.arrayToDataTable([
      ['Time', 'Basement'],
      <?php
      for($i=0;$i<$cntr;$i++){
          echo "['" . date("G:i:s",$chartHum[$i][0]) . "'," . $chartHum[$i][1] . "]";
          //echo "['" .$chartHum[$i][0] . "'," . $chartHum[$i][1] . "]";
          if($i < $cntr-1) {
              echo ",\n";
          }
      }
      ?>
         ]);
         
         var options = {
         title: '% Humidity',
         hAxis: {title: 'Time',  titleTextStyle: {color: '#333'}},
         vAxis: {minValue: 47}
         };

         var chart = new google.visualization.AreaChart(document.getElementById('humidchart_div'));
         chart.draw(data, options);
                    }


      <!-- BATTERY CHART-->              
      function drawBatteryChart() {
      var data = google.visualization.arrayToDataTable([
      ['Time', 'Basement'],
      <?php
      for($i=0;$i<$cntr;$i++){
          echo "['" . date("G:i:s",$chartBat[$i][0]) . "'," . $chartBat[$i][1] . "]";
          //echo "['" .$chartBat[$i][0] . "'," . $chartBat[$i][1] . "]";
          if($i < $cntr-1) {
              echo ",\n";
          }
      }
      ?>
         ]);
         
         var options = {
         title: 'Battery Level',
         hAxis: {title: 'Time',  titleTextStyle: {color: '#333'}},
         vAxis: {minValue: 1.8}
         };

         var chart = new google.visualization.AreaChart(document.getElementById('batterychart_div'));
         chart.draw(data, options);
         }                    
                    
    </script>
  </head>
  <body>
    <div id="tempchart_div" style="width: 100%; height: 500px;"></div>
    <div id="humidchart_div" style="width: 100%; height: 500px;"></div>    
    <div id="batterychart_div" style="width: 100%; height: 500px;"></div>    
  </body>
</html>
