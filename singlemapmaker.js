jQuery(document).ready(function($){	
	
	var postmap = L.map("postmap").setView([s_geo[1], s_geo[2]], 13);
	$("#postmap").height(300).width(400);
	postmap.invalidateSize();
	L.tileLayer.provider('Thunderforest.Landscape').addTo(postmap);

	var marker = L.marker([s_geo[1], s_geo[2]]).addTo(postmap);
	
});
