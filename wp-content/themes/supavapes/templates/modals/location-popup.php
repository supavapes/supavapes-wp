<?php
/**
 * Location popup. Where user can change/update their location.
 */
?>
<div class="location-popup">
	<div class="overlay"></div>
	<div class="location-popup-content">
		<span class="location-popup-close">
			<svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M0.715756 20.4308L20.1773 0.969284L22.2927 3.08467L2.83114 22.5462L0.715756 20.4308ZM0.480713 2.80262L2.50208 0.78125L22.4807 20.7599L20.4593 22.7812L0.480713 2.80262Z" fill="white"></path>
			</svg>				
		</span>
		<div class="location-popup-content-box">
			<div class="location-popup-content-detail">
				<div class="location-popup-content-main">
                    <h2>Location</h2>
                    <div class="custom-location-wrap">
                        <div class="custom-location-input-box">
                            <label>Address</label>
                            <input type="text" placeholder="Enter Your Address" class="type-address" required="" value="">
                        </div>
                        <div class="custom-location-buttons">
                            <a href="" class="button">
                            Detect Me
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 11.25C10.2426 11.25 11.25 10.2426 11.25 9C11.25 7.75736 10.2426 6.75 9 6.75C7.75736 6.75 6.75 7.75736 6.75 9C6.75 10.2426 7.75736 11.25 9 11.25Z" fill="white"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M2.34333 7.875C2.81754 5.04843 5.04843 2.81754 7.875 2.34333V0H10.125V2.34333C12.9516 2.81754 15.1824 5.04843 15.6566 7.875H18V10.125H15.6566C15.1824 12.9516 12.9516 15.1824 10.125 15.6566V18H7.875V15.6566C5.04843 15.1824 2.81754 12.9516 2.34333 10.125H0V7.875H2.34333ZM4.5 9C4.5 6.51472 6.51472 4.5 9 4.5C11.4852 4.5 13.5 6.51472 13.5 9C13.5 11.4852 11.4852 13.5 9 13.5C6.51472 13.5 4.5 11.4852 4.5 9Z" fill="white"/>
                                </svg>
                            </a>
                            <a href="#" class="button">Enter Manually</a>
                        </div>
                    </div>
                    <div id="location-map" style="width: 100%; height: 500px;"></div>
                    <button type="submit" class="button submit-location">Update my location</button>
				</div>
                <div class="location-popup-form">
	<div class="overlay"></div>
	<div class="location-popup-form-content">
		<span class="location-popup-form-close">
			<svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M0.715756 20.4308L20.1773 0.969284L22.2927 3.08467L2.83114 22.5462L0.715756 20.4308ZM0.480713 2.80262L2.50208 0.78125L22.4807 20.7599L20.4593 22.7812L0.480713 2.80262Z" fill="white"></path>
			</svg>				
		</span>
		<div class="location-popup-form-content-box">
			<div class="location-popup-form-content-detail">
                <h2>Please fill the form</h2>
                <form class="menual-location">
                    <div class="menual-location-form-group">
                        <label>Country/Region</label>
                        <input>
                    </div>
                </form>
			</div>
		</div>
	</div>
</div> 
			</div>
		</div>
	</div>
</div> 
    <!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM&callback=initMap" defer></script>
    <script>
        function initMap() {
            var map;
            var bounds = new google.maps.LatLngBounds();
            var mapOptions = {
                mapTypeId: 'roadmap'
            };

            map = new google.maps.Map(document.getElementById("location-map"), mapOptions);
            map.setTilt(50);

            var markers = [
                ['Supa Vapes Hawkesbury', 45.60773945746124, -74.58492574601854],
                ['Supa Vapes 729 Walkley Rd', 45.362812274369, -75.68263443001749]
            ];

            var infoWindowContent = [
                ['<div class="info_content">' +
                '<h2>Supa Vapes Hawkesbury</h2>' +
                '<h3>1502 Main St E, Hawkesbury, ON K6A 1C7, Canada</h3>' +
                '</div>'],
                ['<div class="info_content">' +
                '<h2>Supa Vapes 729 Walkley Rd</h2>' +
                '<h3>729 Walkley Rd, Ottawa, ON K1V 6R6, Canada</h3>' +
                '</div>']
            ];

            var infoWindow = new google.maps.InfoWindow(), marker, i;

            for (i = 0; i < markers.length; i++) {
                var position = new google.maps.LatLng(markers[i][1], markers[i][2]);
                bounds.extend(position);
                marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: markers[i][0]
                });

                google.maps.event.addListener(marker, 'click', (function(marker, i) {
                    return function() {
                        infoWindow.setContent(infoWindowContent[i][0]);
                        infoWindow.open(map, marker);
                    }
                })(marker, i));
            }

            map.fitBounds(bounds);

            var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
                // Adjust the zoom based on the screen width
                var zoomLevel = 10;
                if (window.innerWidth < 768) { // For devices with width < 768px (like phones)
                    zoomLevel = 8;
                } else if (window.innerWidth < 1024) { // For devices with width < 1024px (like tablets)
                    zoomLevel = 9;
                }
                this.setZoom(zoomLevel);
                google.maps.event.removeListener(boundsListener);
            });
        }

        window.initMap = initMap;
    </script> -->
