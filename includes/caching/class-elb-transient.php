<?php

namespace EasyLiveblogs\Caching;

class Transient {
	public static function init() {
		return new self();
	}

	public function __construct() {
		add_filter( 'elb_feed_from_cache', array( $this, 'get_feed' ), 10, 2 );
		add_action( 'elb_process_feed_result', array( $this, 'set_feed' ), 10, 2 );
		add_action( 'elb_purge_cache', array( $this, 'purge_feed' ) );
	}

	public function get_feed( $contents, $liveblog_id ) {
		return get_transient( 'elb_' . $liveblog_id . '_cache' );
	}

	public function set_feed( $liveblog_id, $contents ) {
		set_transient( 'elb_' . $liveblog_id . '_cache', $contents, ( 5 * MINUTE_IN_SECONDS ) );
	}

	public function purge_feed( $liveblog_id ) {
		delete_transient( 'elb_' . $liveblog_id . '_cache' );
	}
}
