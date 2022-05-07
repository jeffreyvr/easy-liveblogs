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

		$feed = FeedFactory::make( $request->get_param( 'id' ) );

		if ( elb_get_option( 'cache_enabled', false ) ) {
			set_transient( 'elb_' . $request->get_param( 'id' ) . '_cache', $feed, ( 5 * MINUTE_IN_SECONDS ) );
		}

		return $feed;
	}
}
