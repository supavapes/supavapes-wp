<?php
if ( isset( $_GET['show_error'] ) ) {
	ini_set( 'display_errors', true );
	ini_set( 'display_startup_errors', true );
	error_reporting( E_ALL );
}