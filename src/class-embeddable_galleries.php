<?php

namespace Embeddable_Galleries;

/**
 * Class Embeddable_Galleries
 * @package Embeddable_Galleries
 */
class embeddable_galleries {

	/**
	 * Reference to an instance of this class
	 *
	 * @var embeddable_galleries
	 */
	private static $instance;

	/**
	 * @var string The rewrite endpoint to use
	 */
	const rewrite_endpoint = 'embedgallery';

	/**
	 * Returns always the same instance of this class.
	 *
	 * @return Embeddable_Galleries An instance of this class
	 */
	static function get_instance() {

		if( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Constructor of the class.
	 *
	 * Adds all action hooks.
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'plugin_textdomain' ) );
		add_action( 'init', array( $this, 'add_rewrite_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		register_activation_hook( __file__, array( $this, 'plugin_activation' ) );
		register_deactivation_hook( __file__, array( $this, 'plugin_deactivation' ) );

	}

	/**
	 * Loads the plugin textdomain to enable translation.
	 *
	 * @see load_plugin_textdomain()
	 */
	function plugin_textdomain() {
		load_plugin_textdomain( 'embeddable-galleries', false, dirname( plugin_basename( __file__ ) ) . '/lang' );
	}

	/**
	 * Runs on plugin activation.
	 *
	 * Adds the rewrite endpoint and flushes WordPress' rewrite rules.
	 *
	 * @see Embeddable_Galleries::add_rewrite_endpoint()
	 */
	function plugin_activation() {
		$this->add_rewrite_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * Flushes WordPress' rewrites rules.
	 *
	 * @see flush_rewrite_rules()
	 */
	function plugin_deactivation() {
		flush_rewrite_rules();
	}

	/**
	 * Enqueue needed javascript files
	 */
	function enqueue_scripts() {
		global $post;

		if ( is_singular() && has_shortcode( $post->post_content, 'gallery' ) ) {
			wp_enqueue_script(
				'embeddable-galleries',
				plugins_url( '/js/embeddable-galleries.js' , dirname(__FILE__) ),
				array( 'jquery-ui-dialog' ),
				'0.1',
				true
			);

			$localize_array = array(
				'embed_text' => __( 'Embed Gallery', 'embeddable-galleries' ),
				'embed_url' => $this->get_gallery_embed_link()
			);
			wp_localize_script( 'embeddable-galleries', 'embeddable_galleries', $localize_array );
		}
	}

	/**
	 * Adds the rewrite endpoint used by the plugin.
	 *
	 * Flushes the rewrite rules afterwards
	 *
	 * @see add_rewrite_endpoint()
	 */
	function add_rewrite_endpoint() {
		add_rewrite_endpoint( $this::rewrite_endpoint, EP_PERMALINK | EP_PAGES );
	}

	/**
	 * Returns the embed link for a specific gallery
	 *
	 * @param int    $post_id
	 * @param string $instance
	 *
	 * @return string $url The embed URL
	 */
	private function get_gallery_embed_link( $post_id = 0, $instance = '' ) {
		$post_id = absint( $post_id );

		if ( ! $post_id )
			$post_id = get_the_ID();

		if ( '' != get_option('permalink_structure') ) {
			if ( 'page' == get_option('show_on_front') && $post_id == get_option('page_on_front') )
				$url = _get_page_link( $post_id );
			else
				$url = get_permalink($post_id);

			$url = trailingslashit($url) . trailingslashit($this::rewrite_endpoint) . $instance;
			$url = user_trailingslashit($url);
		} else {
			$type = get_post_field('post_type', $post_id);
			if ( 'page' == $type )
				$url = add_query_arg( array( $this::rewrite_endpoint => $instance, 'page_id' => $post_id ), home_url( '/' ) );
			else
				$url = add_query_arg( array( $this::rewrite_endpoint => $instance, 'p' => $post_id ), home_url( '/' ) );
		}

		return apply_filters('embeddable_galleries_embed_link', $url);
	}

	function template_redirect() {
		global $wp_query;

		// if this is not a request for us, skip
		if ( ! isset( $wp_query->query_vars[$this::rewrite_endpoint] ) )
			return;

		// if this is not a singular query and there's no shortcode in the content, skip
		if ( ! ( is_singular() && has_shortcode( get_post()->post_content, 'gallery' ) ) )
			return;

		$query_var = get_query_var( $this::rewrite_endpoint ) ? (int) get_query_var( $this::rewrite_endpoint ) : 1;

		$gallery = $this->get_post_gallery( $query_var );

		// show the gallery
		$this->show_embedded_content( $gallery, $query_var );
		exit;
	}

	/**
	 * Outputs the gallery of a given post.
	 *
	 * This is the content shown in the embedded iframe.
	 *
	 * @param array $gallery  The gallery to output
	 * @param int   $instance The instance no. of the gallery
	 */
	private function show_embedded_content( $gallery, $instance ) {
		$columns = $gallery['columns'];

		$selector = "gallery-{$instance}";
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
		$float = is_rtl() ? 'right' : 'left';

		$gallery_style = "
		<style type='text/css'>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
			/* see gallery_shortcode() in wp-includes/media.php */
		</style>";

		$output = $gallery_style . "\n\t\t" . gallery_shortcode( $gallery );

		echo apply_filters( 'embeddable_galleries_output', $output, $gallery );
	}

	/**
	 * Returns a specific gallery in a given post.
	 *
	 * @param int $instance States which gallery instance in this post should be returned.
	 *
	 * @return array The specific gallery
	 */
	private function get_post_gallery( $instance = 1 ) {

		if ( 1 >= $instance )
			$instance = 1;

		//$post_galleries = get_post_galleries( get_the_id(), $html = false );
		$post_galleries = $this->get_post_galleries( get_the_id() );

		return $post_galleries[(int)$instance-1];

	}

	/**
	 * Returns all the galleries in a given post.
	 *
	 * @param mixed $post Post ID or object
	 *
	 * @return array $galleries All found galleries in a post
	 */
	private function get_post_galleries( $post ) {
		if ( ! $post = get_post( $post ) )
			return array();

		if ( ! has_shortcode( $post->post_content, 'gallery' ) )
			return array();

		$galleries = array();
		if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $shortcode ) {
				if ( 'gallery' === $shortcode[2] )
					$galleries[] = shortcode_parse_atts( $shortcode[3] );
			}
		}

		return $galleries;
	}

} 