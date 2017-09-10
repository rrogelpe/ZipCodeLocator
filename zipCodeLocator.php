<!DOCTYPE html>
<!-- Demonstrates retrieving geolocation data from the device -->
<!-- Rodrigo Rogel -->
<html>
<body>
	<div>
		<?php
			$db_conn = mysqli_connect("localhost", "root", "");
			if (!$db_conn)
				die("Unable to connect: " . mysqli_connect_error());  // die is similar to exit

			if (isset($_GET["button"]) ) {
				if($_GET["button"] == "Create DB" ) {
					mysqli_query($db_conn, "CREATE DATABASE IF NOT EXISTS WebTech;");

					mysqli_select_db($db_conn, "WebTech");

					$cmd = "CREATE TABLE tblState(
								zipcode varchar(5) NOT NULL PRIMARY KEY,
								city varchar(10),
								state varchar(5),
								lat float(6,2),
								lon float(6,2),
								timediff int(3)
					);";
					mysqli_query($db_conn, $cmd);
					$cmd = "LOAD DATA LOCAL INFILE 'zip_codes_usa.csv' INTO TABLE tblState FIELDS TERMINATED BY ',';";
					mysqli_query($db_conn, $cmd);
				}

				if($_GET["button"] == "Drop   DB" ){
					$db_conn = mysqli_connect("localhost", "root", "");
					if (!$db_conn)
						die("Unable to connect: " . mysqli_connect_error());  // die is similar to exit

					$retval = mysqli_query($db_conn , "DROP DATABASE WebTech;");
					if(!$retval )
						die('Unable to delete database: ' . mysqli_error($db_conn));
				}
				mysqli_close($db_conn);
			}
		?>

	</div>

	<link rel="stylesheet" href="zipCodeLocator.css">
	<div id="demo"> </div>

	<div class="outsideblock block">
		<div class="insideblock">
			<form action="zipCodeLocator.php" method="get">
				&nbsp;<p id="title" class="inline makelean">THE COM214 ZIP CODE LOCATOR</p>
		
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<input class="button inline" type="submit" name="button" value="Create DB"/>
				<input class="button inline" type="submit" name="button" value="Drop   DB"/>
			</form>
		</div>
	</div>

	<div id="canvasholder" class="block">
      <canvas id="myCanvas" width="1127" height="600" >
        Your browser does not support the canvas element.
      </canvas>
    </div>

    <div class="outsideblock block">
    	<div class="insideblock">
    		<form action="zipCodeLocator.php" method="get"> &nbsp;
		        LATITUDE:   <input type="text" id="lat" name="latitude" readonly>
		        LONGITUDE:  <input type="text" id="lon" name="longitude" readonly>
	    		
	    		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	    		<input class="button inline" type="submit" name="button" value="List Nearby Zip Codes"/>

	    		&nbsp;&nbsp; <p class="inline makelean">Items per page</p>
	    		<select class="inline ddmenu" id="ddvalue" name="showings">
				 	<option value="5">5</option>
				 	<option value="10">10</option>
				 	<option value="15">15</option>
				 	<option value="20">20</option>
				</select>
			</form>
    	</div>

    	<div id="tablediv">
    		<?php

	    		$db_conn = mysqli_connect("localhost", "root", "");

				if (!$db_conn)
					die("Unable to connect: " . mysqli_connect_error());  // die is similar to exit

	    				function convertEST($deeznuts){
							$time = $deeznuts + 5;
							return $time;
						}

						function latLonToMiles($lat1, $lon1, $lat2, $lon2){  //Haversine formula
					        $R = 3961;  // radius of the Earth in miles
					        $dlon = ($lon2 - $lon1)*M_PI/180;
					        $dlat = ($lat2 - $lat1)*M_PI/180;
					        $lat1 *= M_PI/180;
					        $lat2 *= M_PI/180;
					        $a = pow(sin($dlat/2),2) + cos($lat1) * cos($lat2) * pow(sin($dlon/2),2) ;
					        $c = 2 * atan2( sqrt($a), sqrt(1-$a) ) ;
					        $d = $R * $c;
					        $d = number_format((float)$d,2,'.','');
					        return $d;
	    				}
	    		if (isset($_GET["button"])) {

	    			if($_GET["button"] == "List Nearby Zip Codes"){

	    				if(mysqli_select_db($db_conn,"WebTech")) {
	    					$currentLat = $_GET["latitude"];
							$currentLon = $_GET["longitude"];
							$showings   = $_GET["showings"];

							$cmd = "SELECT *,SQRT(POW((lat-$currentLat),2)+POW((lon-$currentLon),2)) AS distance 
							FROM tblState ORDER BY distance ASC limit $showings";

							$records = mysqli_query($db_conn, $cmd);

							echo( "\t\t<table id='table'> <tr> <th>Zip Code Code</th> <th>City</th> <th>State</th> <th>Lan</th> <th>Lon</th> <th>Distance (miles)</th> 
							<th>Time Diff(ET)</th> </tr>" . PHP_EOL  );

							while($row = mysqli_fetch_array($records)) {
		
								echo( "<tr > 
									   <td class='yellow fsize'>" . $row['zipcode'] . "</td> <td class='blue fsize'>" . $row['city'] . "</td> <td class='blue fsize'>" .
									   $row['state'] . "</td> <td class='red fsize'>" . $row['lat'] . "</td> <td class='red fsize'>" . $row['lon'] . "</td> <td class='fsize'>". latLonToMiles($currentLat,$currentLon,$row['lat'],$row['lon']) . "</td> 
								 	   <td class='fsize'>" . convertEST($row['timediff']) . "</td> </tr>" . PHP_EOL );
							}
							echo("\t\t</table>" . PHP_EOL . "\t");
							mysqli_close($db_conn);

	    				}
	    			}
    			}
    			
    		?>
    	</div>
    </div>

	<script>
		var zoom = 4;
		var xpos;
		var ypos;

		var latpos;
		var lonpos;

		var ddval;

		var items;
		var x = document.getElementById("demo");
		
		function displayMap(position){
			var lat = 28.75;
			var lon = -97.35;
			var img_url="http://maps.googleapis.com/maps/api/staticmap?center="
					 +lat+','+lon+"&zoom="+zoom+"&size=800x600&sensor=false";
			var canv=document.getElementById("myCanvas");
			var c=canv.getContext("2d");
			var img = new Image();
			var w, h;

			img.onload = function(){
				w=canv.width;		// resize the canvas to the new image size
				h=canv.height;
				h += 477.5;
				//c.drawImage(img, canv.width / 2 - img.width / 2, canv.height / 2 - img.height / 2, w, h);
				c.drawImage(img, 0, 0, w, h);
				drawMarker();
			}
			
			// console.log(xpos+ ", " +ypos);
      		img.src = img_url;
		}

		function getMousePos(canvas, events){
	  		var obj = canvas;
	  		var top = 0, left = 0;
				var mX = 0, mY = 0;
	 			while (obj && obj.tagName != 'BODY') { //accumulate offsets up to 'BODY'
	      		top += obj.offsetTop;
	      		left += obj.offsetLeft;
	      		obj = obj.offsetParent;
	  		}
	  		mX = events.clientX - left + window.pageXOffset;
	  		mY = events.clientY - top + window.pageYOffset;
	    	return { x: mX, y: mY };
		}

		function showError(error){
			switch(error.code) {
				case error.PERMISSION_DENIED:
					x.innerHTML="User denied the request for Geolocation."
					break;
				case error.POSITION_UNAVAILABLE:
					x.innerHTML="Location information is unavailable."
					break;
				case error.TIMEOUT:
					x.innerHTML="The request to get user location timed out."
					break;
				case error.UNKNOWN_ERROR:
					x.innerHTML="An unknown error occurred."
					break;
			}
		}

		function drawMarker() {
			var canvas = document.getElementById('myCanvas');
		    var context = canvas.getContext('2d');
		    var centerX = xpos;
		    var centerY = ypos;
		    var radius = 25;

		    context.beginPath();
		    context.arc(centerX,centerY,radius,0,2*Math.PI,false);
		    context.fillStyle = "rgba(255, 255, 255, 0.5)";
		    context.fill();
		    context.lineWidth = 3;
		    context.strokeStyle = "black";
		    context.stroke();

		    radius = 8;
		    context.beginPath();
		    context.arc(centerX,centerY,radius,0,2*Math.PI,false);
		    context.fillStyle = "white";
		    context.fill();

		    radius = 3;
		    context.beginPath();
		    context.arc(centerX,centerY,radius,0,2*Math.PI,false);
		    context.fillStyle = "black";
		    context.fill();
		}

		window.onload = function() {

			var canvas = document.getElementById('myCanvas');

			if(typeof(Storage)!=="undefined") {
				if(!localStorage.getItem("saveX") && !localStorage.getItem("saveY")){
					localStorage.setItem("saveX",600);
	  				localStorage.setItem("saveY",300);
  				}
  				if(!localStorage.getItem("saveLat") && !localStorage.getItem("saveLon")){
  					localStorage.setItem("saveLat",(-125.07+(.043936*localStorage.getItem("saveX"))).toFixed(4));
	  				localStorage.setItem("saveLon",(49.105-(.038403*localStorage.getItem("saveY"))).toFixed(4));
  				}
  				
  				if(!localStorage.getItem("saveVal")){
  					localStorage.setItem("saveVal",5);
  				}
			}
			
    		canvas.addEventListener('mousedown', function(events){

	        	var mousePos = getMousePos(canvas, events);

  				xpos = localStorage.saveX = mousePos.x;
  				ypos = localStorage.saveY = mousePos.y;	

  				var val = document.getElementById("ddvalue").value;
		  		ddval = localStorage.saveVal = document.getElementById("ddvalue").value;
		  		val.value = ddval;
		  			
		  		displayMap();

	   		  	var tx = document.getElementById("lon");
	   		  	latpos = localStorage.saveLat = (-125.07+(.043936*xpos)).toFixed(4);
	   		  	tx.value = latpos;

    			var ty = document.getElementById("lat");
    			lonpos = localStorage.saveLon =  (49.105-(.038403*ypos)).toFixed(4)
		  		ty.value = lonpos;

			});

			xpos = localStorage.getItem("saveX");
			ypos = localStorage.getItem("saveY");
			var val = document.getElementById("ddvalue");
		  	val.value = localStorage.getItem("saveVal");
			
			displayMap();

			var tx = document.getElementById("lon");
		  	tx.value = localStorage.getItem("saveLat");
    		var ty = document.getElementById("lat");
		  	ty.value = localStorage.getItem("saveLon");
		  	

		}
	</script>
</body>
</html>
