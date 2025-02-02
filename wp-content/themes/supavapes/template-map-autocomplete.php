<?php
/**
 * Template Name: Google Map Autocomplete
 * 
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
                  <h2><?php esc_html_e( 'Location', 'supavapes' ); ?></h2>
                  <button type="submit" class="button submit-location">
                     <?php esc_html_e( 'Update my location', 'supavapes' ); ?> 
                     <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.3727 3.56366L13.1727 0.36366C13.1228 0.31423 13.0637 0.275123 12.9988 0.248582C12.9338 0.222041 12.8642 0.208587 12.794 0.208993H2.12734C1.703 0.208993 1.29603 0.377564 0.995973 0.677622C0.695915 0.97768 0.527344 1.38465 0.527344 1.80899V14.609C0.527344 15.0333 0.695915 15.4403 0.995973 15.7404C1.29603 16.0404 1.703 16.209 2.12734 16.209H14.9273C15.3517 16.209 15.7586 16.0404 16.0587 15.7404C16.3588 15.4403 16.5273 15.0333 16.5273 14.609V3.94232C16.5277 3.87213 16.5143 3.80255 16.4877 3.73757C16.4612 3.67259 16.4221 3.61349 16.3727 3.56366ZM10.6607 1.27566V4.47566H6.39401V1.27566H10.6607ZM4.26067 15.1423V11.409C4.26067 11.2675 4.31687 11.1319 4.41688 11.0319C4.5169 10.9318 4.65256 10.8757 4.79401 10.8757H12.2607C12.4021 10.8757 12.5378 10.9318 12.6378 11.0319C12.7378 11.1319 12.794 11.2675 12.794 11.409V15.1423H4.26067ZM15.4607 14.609C15.4607 14.7504 15.4045 14.8861 15.3045 14.9861C15.2044 15.0861 15.0688 15.1423 14.9273 15.1423H13.8607V11.409C13.8607 10.9846 13.6921 10.5777 13.392 10.2776C13.092 9.97756 12.685 9.80899 12.2607 9.80899H4.79401C4.36966 9.80899 3.9627 9.97756 3.66264 10.2776C3.36258 10.5777 3.19401 10.9846 3.19401 11.409V15.1423H2.12734C1.98589 15.1423 1.85024 15.0861 1.75022 14.9861C1.6502 14.8861 1.59401 14.7504 1.59401 14.609V1.80899C1.59401 1.66754 1.6502 1.53189 1.75022 1.43187C1.85024 1.33185 1.98589 1.27566 2.12734 1.27566H5.32734V4.47566C5.32734 4.75855 5.43972 5.02987 5.63976 5.2299C5.8398 5.42994 6.11111 5.54232 6.39401 5.54232H10.6607C10.9436 5.54232 11.2149 5.42994 11.4149 5.2299C11.615 5.02987 11.7273 4.75855 11.7273 4.47566V1.27566H12.5753L15.4607 4.16099V14.609Z" fill="white"/>
                     </svg>
                  </button>
               </div>
               <div class="custom-location-wrap">
                  <!-- <div class="custom-location-input-box">
                     <label><?php //esc_html_e( 'Address', 'supavapes' ); ?></label>
                     <input type="text" placeholder="Enter Your Address" class="type-address" id="pac-input" required="" value="">
                  </div> -->

                    <div class="pac-card" id="pac-card">
                        <div>
                            <div id="title">Autocomplete search</div>
                            <br />
                        </div>
                        <div id="pac-container">
                            <input id="pac-input" type="text" placeholder="Enter a location" />
                        </div>
                    </div>

                  <div class="custom-location-buttons">
                     <a href=""><?php esc_html_e( 'Detect Me', 'supavapes' ); ?></a>
                     <button class="enter-menual-btn"><?php esc_html_e( 'Enter Manually', 'supavapes' ); ?></button>
                  </div>
               </div>
               <div id="location-map" style="width: 100%; height: 500px;"></div>
            </div>
            
         </div>
      </div>
   </div>
</div>
<!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM&libraries=places"></script> -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM&callback=initMap&libraries=places&v=weekly" defer></script>
<script>
  function initMap() {
    const map = new google.maps.Map(document.getElementById("location-map"), {
      center: { lat: 40.749933, lng: -73.98633 }, // Default map center
      zoom: 13,
      mapTypeControl: false,
    });

    const autocompleteInput = document.getElementById("pac-input");
    const autocomplete = new google.maps.places.Autocomplete(autocompleteInput, {
      fields: ["formatted_address", "geometry", "name"],
      strictBounds: false,
    });

    // Bias the autocomplete predictions towards current map's viewport.
    autocomplete.bindTo("bounds", map);

    // InfoWindow for displaying the place information
    const infowindow = new google.maps.InfoWindow();
    const marker = new google.maps.Marker({
      map,
      anchorPoint: new google.maps.Point(0, -29),
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

      // Display place info in the InfoWindow
      infowindow.setContent(
        '<div><strong>' + place.name + '</strong><br>' +
        'Address: ' + place.formatted_address + '</div>'
      );
      infowindow.open(map, marker);
    });
  }
  window.initMap = initMap;
</script>