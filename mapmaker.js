jQuery(document).ready(function($){
	
	var checker = 0;
	var $map = $( "<div id='map'/>" );	
	$( "body" ).append( $map);
	$( "#map" ).height(250);
	$( "#map" ).insertAfter( $( "#header" ) );
	
	var map = L.map("map");
		if (typeof s_geo !== 'undefined'){
			map.setView([s_geo[1],s_geo[2]], 8, {animate: false});
		}
		else{
			map.setView([45.403597, -88.485859], 4, {animate: false});
		}
	L.tileLayer.provider('OpenStreetMap.HOT').addTo(map);

	function createMarkers() {
		for (var i = 0; i < geo.length; i++) {
			(function(geoarray) {
				var marker = L.marker([geo[i][1], geo[i][2]]).addTo(map);
				marker.on('click', function(response) {
					onMarkerClick(response, geoarray);
					});
				})(geo[i]);
			}
	}
	
	function onMarkerClick(response, geo){
		location.href = location.protocol + '/wordpress/?p=' + geo[0];
		checker = 1;
	}
	
	createMarkers();
	
});
