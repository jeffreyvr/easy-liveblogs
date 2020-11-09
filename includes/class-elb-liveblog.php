<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELB_Liveblog {
	private $liveblog_post_id;
	private $showposts = 2;

	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'init' ), 0 );
		add_action( 'wp_ajax_nopriv_elb_update_liveblog', array( $this, 'update' ), 10 );
		add_action( 'wp_ajax_elb_update_liveblog', array( $this, 'update' ) );
		add_action( 'wp_ajax_nopriv_elb_load_more', array( $this, 'load_more' ) );
		add_action( 'wp_ajax_elb_load_more', array( $this, 'load_more' ) );
	}

	/**
	 * Init
	 */
	public function init() {
		global $post;

		if ( elb_is_liveblog() && is_singular( elb_get_supported_post_types() ) ) {
			$this->liveblog_post_id = $post->ID;
			$this->showposts        = elb_get_show_entries();

			add_filter( 'body_class', array( $this, 'theme_body_class' ) );
			add_filter( 'the_content', array( $this, 'liveblog' ), 1, 1 );
			add_action( 'wp_head', array( $this, 'add_metadata' ) );
		}
	}

	/**
	 * Theme body class
	 *
	 * @param  array $classes
	 * @return array
	 */
	public function theme_body_class( $classes ) {
		$classes[] = 'elb-theme-' . elb_get_theme();

		return $classes;
	}

	/**
	 * Liveblog
	 */
	public function liveblog( $content ) {
		global $post;

		if ( $post->ID != $this->liveblog_post_id ) {
			return $content;
		}

		// AMP is not supported at this moment
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			$content .= '<p>' . sprintf( __( '<a class="elb-view-liveblog-link button" href="%s">View the liveblog</a>', ELB_TEXT_DOMAIN ), get_permalink() . '#elb-liveblog' ) . '</p>';

			return apply_filters( 'elb_liveblog_html', $content );
		}

		$posts = $this->get_posts();

		$content .= do_action( 'elb_before_liveblog', $this->liveblog_post_id, $posts );

		$content .= '<div id="elb-liveblog" class="elb-liveblog">';

		if ( elb_get_liveblog_status() == 'closed' ) {
			$content .= '<div class="elb-liveblog-closed-message">' . __( 'The liveblog has ended.', ELB_TEXT_DOMAIN ) . '</div>';
		}

		$content .= '<button id="elb-show-new-posts" class="elb-button button" style="display: none;"></button>';

		if ( empty( $posts ) ) {
			$content .= '<div class="elb-no-liveblog-entries-message">' . __( 'No liveblog updates yet.', ELB_TEXT_DOMAIN ) . '</div>';
		}

		$content .= '<ul class="elb-liveblog-list">';

		if ( $post = elb_get_highlighted_entry_id() ) {
			setup_postdata( $post );

			$content .= $this->post( $post, array( 'class' => array( 'elb-liveblog-highlighted-post', 'elb-liveblog-post' ) ) );
		}

		wp_reset_postdata();

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $post ) {

				setup_postdata( $post );

				$content .= $this->post( $post );

				wp_reset_postdata();

			}
		}

		$content .= '</ul>';

		$content .= '</div>';

		$content .= do_action( 'elb_after_liveblog', $this->liveblog_post_id, $posts );

		if ( count( $posts ) == $this->showposts ) {
			$content .= '<button id="elb-load-more" class="elb-button button">' . __( 'Load more', ELB_TEXT_DOMAIN ) . '</button>';
		}

		return apply_filters( 'elb_liveblog_html', $content );
	}

	/**
	 * Set posts
	 */
	public function get_posts( $custom_args = array() ) {
		$default_args = array(
			'post_type'  => 'elb_entry',
			'showposts'  => $this->showposts,
			'meta_key'   => '_elb_liveblog',
			'meta_value' => $this->liveblog_post_id,
		);

		$args = wp_parse_args( $custom_args, $default_args );

		return get_posts( $args );
	}

	/**
	 * Post
	 *
	 * Create markup for post item.
	 *
	 * @param  object $post
	 * @return string
	 */
	public function post( $post, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'class' => array( 'elb-liveblog-post' ),
			)
		);

		$classes = apply_filters( 'elb_liveblog_list_item_classes', implode( ' ', $args['class'] ) );

		$content = '<li data-elb-post-datetime="' . get_post_time( 'U' ) . '" data-elb-post-id="' . esc_attr( $post->ID ) . '" class="' . esc_attr( $classes ) . '">';

		ob_start();

		elb_get_template_part( 'post' );

		$content .= ob_get_clean();

		$content .= '</li>';

		return $content;
	}

	/**
	 * Update
	 *
	 * Fetch new posts.
	 */
	public function update() {
		global $post;

		$after    = isset( $_POST['after'] ) ? (string) $_POST['after'] : '';
		$exclude  = isset( $_POST['exclude'] ) ? (array) $_POST['exclude'] : array();
		$liveblog = isset( $_POST['liveblog'] ) ? (int) $_POST['liveblog'] : '';

		$posts = $this->get_posts(
			array(
				'post__not_in' => $exclude,
				'meta_value'   => $liveblog,
				'date_query'   => array(
					'after' => $after,
				),
			)
		);

		$result = array();

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $post ) {
				setup_postdata( $post );

				$result[] = $this->post( $post );
			}
		}

		echo json_encode( $result );

		die();
	}

	/**
	 * Load more
	 */
	public function load_more() {
		global $post;

		$posts = $this->get_posts(
			array(
				'date_query' => array(
					'before' => (string) $_POST['before'],
				),
				'showposts'  => $this->showposts,
			)
		);

		$result = array();

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $post ) {
				setup_postdata( $post );

				$result[] = $this->post( $post );
			}
		}

		echo json_encode( $result );

		die();
	}

	/**
	 * Add meta data to header.
	 *
	 * @return void
	 */
	public function add_metadata() {
		global $post;

		$metadata = array(
			'@type' => 'LiveBlogPosting',
		);

		$liveblog_url = get_permalink();

		foreach ( $this->get_posts( array( 'showposts' => -1 ) ) as $post ) {
			setup_postdata( $post );

			$entry_url = add_query_arg( 'entry', $post->ID, $liveblog_url );

			$entry = array(
				'@type'            => 'BlogPosting',
				'headline'         => get_the_title(),
				'url'              => $entry_url,
				'mainEntityOfPage' => $entry_url,
				'datePublished'    => get_the_date( 'c' ),
				'dateModified'     => get_the_modified_date( 'c' ),
				'articleBody'      => array(
					'@type' => 'Text',
				),
			);

			if ( elb_display_author_name() ) {
				$entry['author'] = array(
					'@type' => 'Person',
					'name'  => get_the_author(),
				);
			}

			$entries[] = $entry;
		}

		wp_reset_postdata();

		$metadata['liveBlogUpdate'] = $entries ?? array();

		$metadata = apply_filters( 'easy_liveblogs_liveblog_metadata', $metadata, $post );
		?>
		<script type="application/ld+json"><?php echo wp_json_encode( $metadata ); ?></script>
		<?php
	}

}
