<?php
/*
 Includes
*/

include_once("includes/functions.php");


$entities = array();
$links = array();
$trees = array();
$templates = array();

/*
Main App
*/
function fetchLinks($target,$type="parent")
{
	global $links;
	$returnArray = array();
	foreach($links as $l)
	{
		if($type == "child" || $type == "both")
		{
			if($l->entitySource == $target)
			{
				$returnArray[$l->entityTarget] = array("type"=>"parent");
			}
		}
		
		if($type == "parent" || $type == "both")
		{
			if($l->entityTarget == $target)
			{
				$returnArray[$l->entitySource] = array("type"=>"child");
			}
		}
	}
	return $returnArray;
}

if(isset($_REQUEST["key"]))
{
	$key = $_REQUEST["key"];
	$rawXML = fetchXML($key);
	if($rawXML == false)
	{
		echo "Invalid Key, please try again.";
		return;
	}
	
	$xml = new SimpleXMLElement($rawXML);
	parseXML($xml);
	
?>




<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Maltego GPS Mapping</title>
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
    <script>
function initialize() {
  
  var mapOptions = {
    zoom: 3,
    center: new google.maps.LatLng(0,0)
  }
  var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
  var LatLngList = new Array();
  
<?php

	foreach($entities as $key=>$entity)
	{
		
		
		if($entity->type == "maltego.GPS")
		{
			$latLong = $entity->properties['gps.coordinate'];
			$targetLinks = fetchLinks($key);
			$tweet = "No Tweet at this Location";
			//echo "$latLong ($key) =>";
			foreach($targetLinks as $tlKey=>$tl)
			{
				//echo "$tlKey";
				if(array_key_exists($tlKey,$entities))
				{
					$ent = $entities[$tlKey];
					if($ent->type == "maltego.Twit")
					{
						$tweet = addslashes($ent->properties["title"]);
					}
					
					
				}
				else
				{
					
					
				}
			}
			$markerName = "m" . md5(microtime()); 
			echo "var $markerName = new google.maps.Marker({position: new google.maps.LatLng(" . $entity->properties['gps.coordinate'] . "),map: map,icon: 'tweetMarker.png',title: '$tweet'});\n";
			
			echo "var infowindow = new google.maps.InfoWindow({
				  content: '<table><tr valign=\'top\'><td><img height=\'50px\' src=\'twitterIcon.png\'/></td><td style=\'padding-top:10px;\'>$tweet</td></tr></table>'
				});
			";
			echo "makeInfoWindowEvent(map, infowindow, $markerName);";
			
			//  Make an array of the LatLng's of the markers you want to show
			echo "LatLngList.push(new google.maps.LatLng(" . $entity->properties['gps.coordinate'] . "));\n";
			
			
			
		}
	}
	
	foreach($entities as $entity)
	{
		if($entity->type == "maltego.CircularArea")
		{
			
			$markerName = "marker" . md5(microtime());
			$long = $entity->properties['longitude'];
			$lat = $entity->properties['latitude'];
			$area = $entity->properties['radius'];
			echo "
			// Add circle overlay and bind to marker
			var circle$markerName = new google.maps.Circle({
			map: map,
			radius: $area,    // 10 miles in metres
			fillColor: '#0000AA',
			fillOpacity: 0.1,
			strokeColor: '#0082CF',
			strokeOpacity: 0.8,
			strokeWeight: 1,
			center: new google.maps.LatLng($lat, $long),
			});
			
			";
		}
	}

?>

var bounds = new google.maps.LatLngBounds();
for (var i = 0; i < LatLngList.length; i++) {
  bounds.extend(LatLngList[i]);
}

//  Fit these bounds to the map
map.fitBounds (bounds);

};

function makeInfoWindowEvent(map, infowindow, marker) {
  google.maps.event.addListener(marker, 'click', function() {
    infowindow.open(map, marker);
  });
}
google.maps.event.addDomListener(window, 'load', initialize);

    </script>
  </head>
  <body>
    <div id="map-canvas"></div>
  </body>
</html>





<?php
}
else
{
	echo "Invalid Key, please try again.";
}

