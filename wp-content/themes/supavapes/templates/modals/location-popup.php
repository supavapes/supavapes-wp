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
        <div class="pre-loader_page" id="loader">
			<div class="loader_row">
				<span class="sv-loader"></span>
			</div>
		</div>
         <div class="location-popup-content-detail">
            <div class="location-popup-content-main">
               <div class="location-popup-header">
                  <h2><?php esc_html_e( 'Location', 'supavapes' ); ?></h2>
                  <button type="button" id="update-user-location" class="button submit-location" data-userselectedstate="" data-userselectedcountry="">
                     <?php esc_html_e( 'Update my location', 'supavapes' ); ?> 
                     <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.3727 3.56366L13.1727 0.36366C13.1228 0.31423 13.0637 0.275123 12.9988 0.248582C12.9338 0.222041 12.8642 0.208587 12.794 0.208993H2.12734C1.703 0.208993 1.29603 0.377564 0.995973 0.677622C0.695915 0.97768 0.527344 1.38465 0.527344 1.80899V14.609C0.527344 15.0333 0.695915 15.4403 0.995973 15.7404C1.29603 16.0404 1.703 16.209 2.12734 16.209H14.9273C15.3517 16.209 15.7586 16.0404 16.0587 15.7404C16.3588 15.4403 16.5273 15.0333 16.5273 14.609V3.94232C16.5277 3.87213 16.5143 3.80255 16.4877 3.73757C16.4612 3.67259 16.4221 3.61349 16.3727 3.56366ZM10.6607 1.27566V4.47566H6.39401V1.27566H10.6607ZM4.26067 15.1423V11.409C4.26067 11.2675 4.31687 11.1319 4.41688 11.0319C4.5169 10.9318 4.65256 10.8757 4.79401 10.8757H12.2607C12.4021 10.8757 12.5378 10.9318 12.6378 11.0319C12.7378 11.1319 12.794 11.2675 12.794 11.409V15.1423H4.26067ZM15.4607 14.609C15.4607 14.7504 15.4045 14.8861 15.3045 14.9861C15.2044 15.0861 15.0688 15.1423 14.9273 15.1423H13.8607V11.409C13.8607 10.9846 13.6921 10.5777 13.392 10.2776C13.092 9.97756 12.685 9.80899 12.2607 9.80899H4.79401C4.36966 9.80899 3.9627 9.97756 3.66264 10.2776C3.36258 10.5777 3.19401 10.9846 3.19401 11.409V15.1423H2.12734C1.98589 15.1423 1.85024 15.0861 1.75022 14.9861C1.6502 14.8861 1.59401 14.7504 1.59401 14.609V1.80899C1.59401 1.66754 1.6502 1.53189 1.75022 1.43187C1.85024 1.33185 1.98589 1.27566 2.12734 1.27566H5.32734V4.47566C5.32734 4.75855 5.43972 5.02987 5.63976 5.2299C5.8398 5.42994 6.11111 5.54232 6.39401 5.54232H10.6607C10.9436 5.54232 11.2149 5.42994 11.4149 5.2299C11.615 5.02987 11.7273 4.75855 11.7273 4.47566V1.27566H12.5753L15.4607 4.16099V14.609Z" fill="white"/>
                     </svg>
                  </button>
               </div>
               <div class="custom-location-wrap">
                    <div class="custom-location-input-box">
                     <label><?php esc_html_e( 'Address', 'supavapes' ); ?></label>
                     <input type="text" placeholder="Enter Your Address" class="type-address" id="pac-input" required="" value="">
                     <span class="address-error-msg"></span>
                  </div>
                  <div class="custom-location-buttons">
                     <a href="javascript:void(0);" id="detect-me-button"><?php esc_html_e( 'Detect Me', 'supavapes' ); ?></a>
                     <button class="enter-menual-btn"><?php esc_html_e( 'Enter Manually', 'supavapes' ); ?></button>
                  </div>
               </div>
               <div id="location-map" style="width: 100%; height: 500px;"></div>
                <div id="infowindow-content">
                    <span id="place-name" class="title" style="color: #000;"></span><br />
                    <span id="place-address" style="color: #000;"></span>
                </div>
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
                        <h2><?php esc_html_e( 'Please fill the form', 'supavapes' ); ?></h2>
                        <form class="menual-location">
                           <div class="menual-location-form-group">
                              <label><?php esc_html_e( 'Country/Region', 'supavapes' ); ?></label>
                              <input type="text" value="Canada" readonly>
                           </div>
                            <div class="menual-location-form-group">
                                <label><?php esc_html_e( 'State/Province', 'supavapes' ); ?></label>
                                <select id="state-province-select">
                                    <option value="" disabled selected><?php esc_html_e( 'Select State/Province', 'supavapes' ); ?></option>
                                    <option value="AB" data-lat="53.7267" data-lng="-113.3100"><?php esc_html_e( 'Alberta', 'supavapes' ); ?></option>
                                    <option value="BC" data-lat="53.7267" data-lng="-127.6476"><?php esc_html_e( 'British Columbia', 'supavapes' ); ?></option>
                                    <option value="MB" data-lat="49.8951" data-lng="-97.1384"><?php esc_html_e( 'Manitoba', 'supavapes' ); ?></option>
                                    <option value="NB" data-lat="46.5653" data-lng="-66.4619"><?php esc_html_e( 'New Brunswick', 'supavapes' ); ?></option>
                                    <option value="NL" data-lat="53.1355" data-lng="-57.6604"><?php esc_html_e( 'Newfoundland and Labrador', 'supavapes' ); ?></option>
                                    <option value="NS" data-lat="44.6820" data-lng="-63.7443"><?php esc_html_e( 'Nova Scotia', 'supavapes' ); ?></option>
                                    <option value="ON" data-lat="43.6532" data-lng="-79.3832"><?php esc_html_e( 'Ontario', 'supavapes' ); ?></option>
                                    <option value="PE" data-lat="46.5107" data-lng="-63.4168"><?php esc_html_e( 'Prince Edward Island', 'supavapes' ); ?></option>
                                    <option value="QC" data-lat="46.8139" data-lng="-71.2082"><?php esc_html_e( 'Quebec', 'supavapes' ); ?></option>
                                    <option value="SK" data-lat="52.9399" data-lng="-106.4509"><?php esc_html_e( 'Saskatchewan', 'supavapes' ); ?></option>
                                </select>
                            </div>
                           <button type="button" id="submit-location-form" class="button submit-location-form"><?php esc_html_e( 'Update my location', 'supavapes' ); ?></button>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM&libraries=places"></script> -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM&callback=initMap&libraries=places&v=weekly" defer></script>
<script>
let map; // Declare map variable outside of the function
let marker; // Declare marker outside to reuse it
let infowindow; // Declare infowindow outside to reuse it
let autocompleteInput; // Move autocompleteInput to a higher scope


function initMap() {
    // Try to get the user's current location using the Geolocation API
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                console.log(userLat);
                console.log(userLng);
                // Initialize the map using the user's current location
                initializeMap(userLat, userLng);
            },
            () => {
                // If user denies geolocation or it's not available, use a default location
                initializeMap(50.000000, -85.000000); // Default lat/lng
            }
        );
    } else {
        // Geolocation is not supported by the browser, fall back to default location
        initializeMap(50.000000, -85.000000); // Default lat/lng
    }
}


function initializeMap(lat, lng) {
    // Initialize the map
    map = new google.maps.Map(document.getElementById("location-map"), {
        center: { lat: lat, lng: lng }, // Use the provided coordinates
        zoom: 5,
        mapTypeControl: false,
    });

    autocompleteInput = document.getElementById("pac-input");
    const updateButton = document.getElementById("update-user-location");
    const autocomplete = new google.maps.places.Autocomplete(autocompleteInput, {
        fields: ["formatted_address", "geometry", "name", "address_components"],
        strictBounds: false,
    });

    // Bias the autocomplete predictions towards current map's viewport
    autocomplete.bindTo("bounds", map);

    // Initialize the infowindow and marker
    infowindow = new google.maps.InfoWindow();
    marker = new google.maps.Marker({
        map: map,
        anchorPoint: new google.maps.Point(0, -29),
        visible: false, // Initially hide the marker
    });

    // Handle place selection from autocomplete suggestions
    autocomplete.addListener("place_changed", () => {
        infowindow.close();
        marker.setVisible(false);

        const place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) {
            window.alert("No details available for input: '" + place.name + "'");
            return;
        }

        // Adjust the map viewport and set marker position
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }
        marker.setPosition(place.geometry.location);
        marker.setVisible(true);

        infowindow.setContent(
            '<div class="location-info-content"><strong>' + place.name + ', Canada</strong><br>' +
            'Address: ' + place.formatted_address + '</div>'
        );
        infowindow.open(map, marker);

        // Extract the state or province and country
        const addressComponents = place.address_components;
        let state = '';
        let country = '';

        for (let i = 0; i < addressComponents.length; i++) {
            const component = addressComponents[i];
            if (component.types.includes("administrative_area_level_1")) {
                state = component.long_name; // Get the state name
            }
            if (component.types.includes("country")) {
                country = component.long_name; // Get the country name
            }
        }

        // Update the autocomplete input field with only state and country
        if (autocompleteInput) {
            autocompleteInput.value = `${state}, ${country}`; // Show only state and country
        }

        // Update data attributes and store the selected location
        if (updateButton) {
            updateButton.setAttribute("data-userselectedstate", state);
            updateButton.setAttribute("data-userselectedcountry", country);
            console.log('data-userselectedstate updated to: ' + state);

            // Store the updated location in local storage or session storage
            localStorage.setItem('selectedState', state);
            localStorage.setItem('selectedCountry', country);
            localStorage.setItem('selectedLat', place.geometry.location.lat());
            localStorage.setItem('selectedLng', place.geometry.location.lng());
        }
    });

    
}

// Function to load previously stored location on popup open
function loadStoredLocation() {
        const storedState = localStorage.getItem('selectedState');
        const storedCountry = localStorage.getItem('selectedCountry');
        const storedLat = parseFloat(localStorage.getItem('selectedLat'));
        const storedLng = parseFloat(localStorage.getItem('selectedLng'));

        if (storedState && storedCountry && !isNaN(storedLat) && !isNaN(storedLng)) {
            // Pre-fill the autocomplete input
            autocompleteInput.value = storedState + ', ' + storedCountry;

            // Update map center and marker position
            map.setCenter({ lat: storedLat, lng: storedLng });
            marker.setPosition({ lat: storedLat, lng: storedLng });
            marker.setVisible(true);

            infowindow.setContent(
                '<div class="location-info-content"><strong>' + storedState + ', ' + storedCountry + '</strong><br></div>'
            );
            infowindow.open(map, marker);

            console.log('Loaded stored location:', storedState, storedCountry);
        }
    }
// Function to update the map and marker based on selected state
function updateLocation() {
    const stateProvinceSelect = document.getElementById("state-province-select");
    const updateButton = document.getElementById("update-user-location");
    console.log("state: "+stateProvinceSelect);
    console.log("updateButton: "+updateButton);
    if (stateProvinceSelect) {
        const selectedOption = stateProvinceSelect.selectedOptions[0];
        const lat = parseFloat(selectedOption.getAttribute("data-lat"));
        const lng = parseFloat(selectedOption.getAttribute("data-lng"));
        const selectedState = selectedOption.text; // Get the state name

        if (!isNaN(lat) && !isNaN(lng)) {
            // Update the map center to the selected state's coordinates
            map.setCenter({ lat: lat, lng: lng });
            map.setZoom(6); // Optional: Set a zoom level that works for the selected area

            // Reposition the marker and show it on the map
            marker.setPosition({ lat: lat, lng: lng });
            marker.setVisible(true);

            infowindow.setContent(
                '<div class="location-info-content"><strong>' + selectedState + ', Canada</strong><br></div>'
            );
            infowindow.open(map, marker);

            // Autofill the selected state/province into the pac-input textbox
            const autocompleteInput = document.getElementById("pac-input");
            if (autocompleteInput) {
                autocompleteInput.value = selectedState + ', Canada'; // Autofill the state name
            }

            // Store the updated location in local storage
            localStorage.setItem('selectedState', selectedState);
            localStorage.setItem('selectedCountry', 'Canada');
            localStorage.setItem('selectedLat', lat);
            localStorage.setItem('selectedLng', lng);

            if (updateButton) {
                updateButton.setAttribute("data-userselectedstate", selectedState);
                updateButton.setAttribute("data-userselectedcountry", "Canada");
                console.log('data-userselectedstate updated to: ' + selectedState);
            }
        }
    }
}

window.initMap = initMap;


jQuery(document).on("click", '#detect-me-button', function(e) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;

            // Update map center and place the marker
            map.setCenter({ lat: lat, lng: lng });
            map.setZoom(15); // Zoom to a closer level for better view of the location

            // Reposition the marker to the detected location
            marker.setPosition({ lat: lat, lng: lng });
            marker.setVisible(true);

            // Reverse geocode to get the city and country
            jQuery.get('https://maps.googleapis.com/maps/api/geocode/json', {
                latlng: lat + ',' + lng,
                key: 'AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM' // Make sure this key is valid
            }, function(response) {
                if (response.status === 'OK') {
                    var result = response.results[0];
                    var city = '';
                    var state = '';
                    var country = '';
                    for (var i = 0; i < result.address_components.length; i++) {
                        var component = result.address_components[i];
                        console.log(component);
                        if (component.types.includes('administrative_area_level_1')) {
                            state = component.long_name;
                        }
                        if (component.types.includes('country')) {
                            country = component.long_name;
                        }
                    }

                    // Check if the detected location is in Canada
                    if (country !== 'Canada') {
                        jQuery('.address-error-msg').text('This location is not allowed.');
                        jQuery("#update-user-location").prop("disabled", true);
                    } else {
                        jQuery("#update-user-location").prop("disabled", false);
                    }

                    // Update the input field with city and country
                    jQuery('#pac-input').val(state + ', ' + country);

                    // Update the infowindow content with the city and country
                    infowindow.setContent(
                        '<div class="location-info-content"><strong>' + city + ', ' + country + '</strong><br></div>'
                    );
                    infowindow.open(map, marker);
                } else {
                    // Handle the error when reverse geocoding fails
                    console.error('Geocoding failed: ' + response.status);
                }
            });
        }, function(error) {
            // Handle geolocation error
            console.error('Geolocation error: ', error);
            jQuery('.address-error-msg').text('Unable to retrieve your location. Please try again.');
        });
    } else {
        // Handle browser that does not support geolocation
        console.error('Geolocation is not supported by this browser.');
        jQuery('.address-error-msg').text('Geolocation is not supported by this browser.');
    }
});

</script>