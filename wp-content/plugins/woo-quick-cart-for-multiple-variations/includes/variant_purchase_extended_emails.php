<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wqcmv_send_mail( $to, $subject, $message, $headers = array(), $attachement = array() ) {
	$headers = wp_parse_args( $headers, array( 'Content-Type: text/html; charset=UTF-8' ) );
	if ( empty( $to ) || empty( $subject ) || empty( $message ) ) {
		return false;
	}

	return wp_mail( $to, $subject, $message, $headers, $attachement );
}