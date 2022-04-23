<?php

namespace EasyLiveblogs\API;

class Feed {
	/**
	 * Setup.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	/**
	 * Register route.
	 *
	 * @return void
	 */
	public function register() {
		register_rest_route(
			'easy-liveblogs/v1',
			'/liveblog/(?P<id>\d+)',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'feed' ),
			)
		);
	}

	/**
	 * Construct the feed.
	 *
	 * @param \WP_REST_Request $request
	 * @return array
	 */
	public function feed( \WP_REST_Request $request ) {
		if ( $feed = get_transient( 'elb_' . $request->get_param( 'id' ) . '_cache' ) ) {
			return $feed;
		}

		$liveblog = elb_get_liveblog( $request->get_param( 'id' ) );

		$entries = $this->get_entries( $request->get_param( 'id' ) );

		$feed = array(
			'title'       => $liveblog->post_title,
			'url'         => get_permalink( $liveblog->ID ),
			'status'      => get_post_meta( $liveblog->ID, '_elb_status', true ),
			'last_update' => $entries[0]->datetime,
			'updates'     => $entries,
		);

		if ( elb_get_option( 'cache_enabled', false ) ) {
			set_transient( 'elb_' . $request->get_param( 'id' ) . '_cache', $feed, ( 5 * MINUTE_IN_SECONDS ) );
		}

		return $feed;
	}

	/**
	 * Get the liveblog entries.
	 *
	 * @param int $liveblog_id
	 * @return array
	 */
	public function get_entries( $liveblog_id ) {
		$args = array(
			'post_type'  => 'elb_entry',
			'showposts'  => -1,
			'meta_key'   => '_elb_liveblog',
			'meta_value' => $liveblog_id,
		);

		return array_map(
			function( $post ) {
				return Entry::fromPost( $post );
			},
			get_posts( $args )
		);
	}
}
