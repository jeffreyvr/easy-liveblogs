<?php

namespace EasyLiveblogs\Caching;

use EasyLiveblogs\API\FeedFactory;

class File {
	public static function init() {
		return new self();
	}

	public function __construct() {
		add_action( 'elb_liveblog_api_endpoint', array( $this, 'overwrite_endpoint' ), 10, 2 );
		add_action( 'elb_purge_cache', array( $this, 'set_feed' ), 10 );
		add_action( 'elb_delete_cache', array( $this, 'delete_feed' ), 10 );
	}

	public function get_feed_path( $liveblog_id, $check_exists = false ) {
		$dir = WP_CONTENT_DIR . '/cache/easy-liveblogs/';

		if ( ! is_dir( $dir ) ) {
			mkdir( $dir );
		}

		$filepath = $dir . '/' . $liveblog_id . '.json';

		if ( $check_exists && ! file_exists( $filepath ) ) {
			return false;
		}

		return $filepath;
	}

	public function get_feed_url( $liveblog_id ) {
		$url = content_url() . '/cache/easy-liveblogs';

		return $url . '/' . $liveblog_id . '.json';
	}

	public function overwrite_endpoint( $url, $post_id ) {
		if ( $this->get_feed_path( $post_id, true ) ) {
			return $this->get_feed_url( $post_id );
		}

		return $url;
	}

	public function set_feed( $liveblog ) {
		$contents = json_encode( FeedFactory::make( $liveblog ) );

		$filepath = $this->get_feed_path( $liveblog );

		file_put_contents( $filepath, $contents );
	}

	public function delete_feed( $liveblog ) {
		wp_delete_file( $this->get_feed_path( $liveblog ) );
	}
}
