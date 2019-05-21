<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get template part
 *
 * @see https://pippinsplugins.com/template-file-loaders-plugins/
 *
 * @param  string $view
 * @return string
 */
function elb_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
	$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'elb_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return elb_locate_template( $templates, $load, false );
}

/**
 * Locate template
 *
 * @param  string|array $template_names
 * @param  boolean $load
 * @param  boolean $require_once
 */
function elb_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Check child theme first
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'elb/' . $template_name ) ) {
			$located = trailingslashit( get_stylesheet_directory() ) . 'elb/' . $template_name;
			break;

			// Check parent theme next
		} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'elb/' . $template_name ) ) {
			$located = trailingslashit( get_template_directory() ) . 'elb/' . $template_name;
			break;

			// Check theme compatibility last
		} elseif ( file_exists( trailingslashit( elb_get_templates_dir() ) . $template_name ) ) {
			$located = trailingslashit( elb_get_templates_dir() ) . $template_name;
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) ) {
		load_template( $located, $require_once );

	} else {

		return $located;
	}
}

/**
 * Get templates dir
 *
 * @return string
 */
function elb_get_templates_dir() {
	return ELB_PATH . 'templates';
}

/**
 * Get liveblogs
 *
 * Get all published liveblogs.
 *
 * @return array
 */
function elb_get_liveblogs() {
	$args = array(
		'post_type' => elb_get_supported_post_types(),
		'post_status' => 'publish',
		'showposts' => -1,
		'meta_query' => array(
			array(
				'key' => '_elb_is_liveblog',
				'compare' => 'EXISTS',
			)
		)
	);

	$args = apply_filters( 'elb_get_liveblogs_args', $args );

	$liveblogs = get_posts( $args );

	$result = array();

	foreach ( $liveblogs as $liveblog ) {
		$result[$liveblog->ID] = $liveblog->post_title;
	}

	return apply_filters( 'elb_get_liveblogs', $result );
}

/**
 * Get liveblogs count
 *
 * @return int
 */
function elb_get_liveblogs_count( $args = array() ) {
	$default_args = apply_filters( 'elb_get_liveblogs_count_args', array(
		'post_status' => array( 'publish', 'draft', 'future', 'trash' ),
		'all_posts' => 1,
		'post_type' => elb_get_supported_post_types(),
		'meta_query' => array(
			array(
				'key' => '_elb_is_liveblog',
				'compare' => 'EXISTS',
			)
		)
	) );

	$args = wp_parse_args( $args, $default_args );

    $result = new WP_Query($args);

	return $result->found_posts;
}

/**
 * Is liveblog
 *
 * Checks if post is liveblog.
 *
 * @return boolean
 */
function elb_is_liveblog() {
	global $post;

	$liveblog = false;

	if ( !empty( $post->ID ) ) {
		if ( get_post_meta( $post->ID, '_elb_is_liveblog', true ) ) {
			$liveblog = true;
		}

	}

	return apply_filters( 'elb_is_liveblog', $liveblog );
}

/**
 * Get liveblog status
 *
 * @param  int $post_id
 * @return string
 */
function elb_get_liveblog_status( $post_id = null ) {
	if ( !$post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	return get_post_meta( $post_id, '_elb_status', true );
}

/**
 * Liveblog status options
 *
 * @return array
 */
function elb_get_liveblog_status_options() {
	return apply_filters( 'elb_liveblog_status_options', array(
		'open' => __( 'Open', ELB_TEXT_DOMAIN ),
		'closed' => __( 'Closed', ELB_TEXT_DOMAIN )
	) );
}

/**
 * Get supported post types
 *
 * @return array
 */
function elb_get_supported_post_types() {
	global $elb_options;

	$post_types = !empty( $elb_options['post_types'] ) ? $elb_options['post_types'] : array( 'post' );

	return apply_filters( 'elb_post_types', $post_types );
}

/**
 * Get update interval
 *
 * @return string
 */
function elb_get_update_interval() {
	global $elb_options;

	$update_interval = !empty( $elb_options['update_interval'] ) ? $elb_options['update_interval'] : 30;

	return apply_filters( 'elb_update_interval', $update_interval );
}

/**
 * Get show entries
 *
 * @return string
 */
function elb_get_show_entries() {
	global $elb_options;

	$show_entries = !empty( $elb_options['show_entries'] ) ? $elb_options['show_entries'] : 10;

	return apply_filters( 'elb_show_entries', $show_entries );
}

/**
 * Get theme
 *
 * @return string
 */
function elb_get_theme() {
	global $elb_options;

	$theme = !empty( $elb_options['theme'] ) ? $elb_options['theme'] : 'light';

	return apply_filters( 'elb_theme', $theme );
}

/**
 * Title prefix
 *
 * @return string
 */
function elb_get_liveblog_title_prefix() {
	return apply_filters( 'elb_liveblog_title_prefix', __( 'Liveblog', ELB_TEXT_DOMAIN ) . ' - ' );
}

/**
 * Custom the_content alternative
 *
 * @return string
 */
function elb_get_entry_content() {
	global $post, $wp_embed;

	$content = do_shortcode( $post->post_content );
	$content = $wp_embed->autoembed( $content );
	$content = wpautop( $content );

	return apply_filters( 'elb_entry_content', $content );
}

/**
 * Entry content
 *
 * @return void
 */
function elb_entry_content() {
	echo elb_get_entry_content();
}

/**
 * Get entry title
 *
 * @return string
 */
function elb_get_entry_title() {
	global $post;

	return apply_filters( 'elb_entry_title', $post->post_title );
}

/**
 * Entry title
 *
 * @return void
 */
function elb_entry_title() {
	echo elb_get_entry_title();
}
