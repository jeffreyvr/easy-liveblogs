<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liveblog shortcode.
 *
 * @param array $atts
 * @return string
 */
function elb_liveblog_shortcode( $atts ) {
    $endpoint = !empty($atts['endpoint']) ? esc_attr($atts['endpoint']) : null;
    $id = !empty($atts['id']) ? esc_attr($atts['id']) : null;

	if ( $endpoint ) {
		$liveblog = ELB_Liveblog::fromEndpoint( $endpoint );
	} elseif ( $id ) {
		$liveblog = ELB_Liveblog::fromId( $id );
	} else {
		return;
	}

	return $liveblog->render();
}

add_shortcode( 'elb_liveblog', 'elb_liveblog_shortcode' );
