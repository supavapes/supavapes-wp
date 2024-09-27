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
                    <div class="location-popup-header">
                        <h2>Location</h2>
                        <button type="submit" class="button submit-location">Update my location</button>
                    </div>
                    <div class="custom-location-wrap">
                        <div class="custom-location-input-box">
                            <label>Address</label>
                            <input type="text" placeholder="Enter Your Address" class="type-address" required="" value="">
                        </div>
                        <div class="custom-location-buttons">
                            <a href="">Detect Me</a>
                            <button class="enter-menual-btn">Enter Manually</button>
                        </div>
                    </div>
                    <div id="location-map" style="width: 100%; height: 500px;"></div>
                    
				</div>
                <div class="location-popup-form">
	<div class="location-popup-form-overlay"></div>
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
                        <input type="text" value="Canada" readonly>
                    </div>

                    <div class="menual-location-form-group">
                        <label>State/Province</label>
                        <select>
                            <option value="" disabled selected>Select State/Province</option>
                            <option value="AB">Alberta</option>
                            <option value="BC">British Columbia</option>
                            <option value="MB">Manitoba</option>
                            <option value="NB">New Brunswick</option>
                            <option value="NL">Newfoundland and Labrador</option>
                            <option value="NS">Nova Scotia</option>
                            <option value="ON">Ontario</option>
                            <option value="PE">Prince Edward Island</option>
                            <option value="QC">Quebec</option>
                            <option value="SK">Saskatchewan</option>
                        </select>
                    </div>

                    <button type="submit" class="button submit-location-form">Update my location</button>
                </form>

			</div>
		</div>
	</div>
</div> 
			</div>
		</div>
	</div>
</div> 
    <script src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap" defer></script>
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
    </script>
