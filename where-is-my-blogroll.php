<?php
/*
 * Plugin Name: Where Is My Blogroll
 * Description: Fügt den WordPress Link Manager wieder ein und bietet dir mit einem Shortcode eine kompakte Ausgabe, für deine Webseite.
 * Version: 1.1.1
 * Author: Pixelbart
 * Author URI: https://pixelbart.de
 * Text Domain: wimb
 * Domain Path: /languages
 * License: MIT License
 * License URI: http://opensource.org/licenses/MIT
 */

/**
 * Prevent direct access
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Activate wordpress link manager
 */
add_filter( 'pre_option_link_manager_enabled', '__return_true' );

/**
 * The shortcode
 */
add_shortcode('blogroll', 'where_is_my_blogroll');
function where_is_my_blogroll($atts)
{
    $a = shortcode_atts( array(
		'orderby'        => 'name',
		'order'          => 'ASC',
		'limit'          => -1,
		'category'       => '',
		'category_name'  => '',
		'hide_invisible' => 1,
		'show_updated'   => 0,
		'include'        => '',
		'exclude'        => '',
		'search'         => ''
	), $atts);

	$bookmarks = get_bookmarks($a);

	$out  = '<ul class="where-is-my-blogroll">';

	// Loop through each bookmark and print formatted output
	foreach ( $bookmarks as $bookmark ) {

		if( $bookmark->link_description ) {

			$out .= sprintf(
				'<li><a href="%s">%s</a><span>%s</span></li>',
				esc_url($bookmark->link_url), // Link url
				esc_html($bookmark->link_name), // Link name
				esc_html($bookmark->link_description) // Link description
			);

		} else {

			$out .= sprintf(
				'<li><a href="%s">%s</a></li>',
				esc_url($bookmark->link_url), // Link url
				esc_html($bookmark->link_name) // Link name
			);

		}
	}

	$out .= '</ul>';

	return $out;
}

/**
 * Echo HTML file with CSS in wp_head
 */
add_action('wp_head','wimb_frontend_css');
function wimb_frontend_css()
{
	include( plugin_dir_path( __FILE__ ) . 'css/wimb-css.html' );
}

/**
 * Echo HTML file with CSS in admin_head
 */
add_action('admin_head','wimb_admin_css');
function wimb_admin_css()
{
	include( plugin_dir_path( __FILE__ ) . 'css/wimb-admin-css.html' );
}

/**
 * Feed reader function
 */
function wimb_read_RSS($url = null)
{
	if($url == null) return false;

    $feed = file_get_contents($url);
	$limit = 5; // Posts limit for feeds

    if(!strlen($feed)) {
        return;
    }

    $data = new SimpleXMLElement($feed);

	$out = '';

	$i = 0;

    foreach($data->channel->item as $item) {

		if (strlen($item->title) >= 50)
			$item->title = mb_substr($item->title, 0, 50, 'utf8'). '&hellip;';

        $out .= sprintf(
			'<li><a href="%s">%s</a></li>',
			esc_url($item->link), // Link url
			esc_html($item->title) // Link title
		);

		$i++;

		if($i == $limit) break;
    }

	return $out;
}

/**
 * Add a dashboard widget with feed from blogroll, only shows the last post.
 */
function wimb_add_dashboard_widgets()
{
	wp_add_dashboard_widget(
		'where-is-my-blogroll-feed',	// Widget slug.
		__('Blogroll Feed','wimb'),	// Title.
		'wimb_dashboard_widget_function' // Display function.
	);
}
add_action( 'wp_dashboard_setup', 'wimb_add_dashboard_widgets' );

/**
 * Dashboard widget callback
 */
function wimb_dashboard_widget_function()
{
	echo sprintf('<div id="wimb-rss-feed" data-loading-text="%s"></div>', __('Einen Moment bitte...','wimb') );
}

/**
 * Request for dashboard widget
 */
add_action( 'wp_ajax_nopriv_wimb_dashboard_widget_ajax_request', 'wimb_dashboard_widget_ajax_request' );
add_action( 'wp_ajax_wimb_dashboard_widget_ajax_request', 'wimb_dashboard_widget_ajax_request' );
function wimb_dashboard_widget_ajax_request()
{
	$args = array(
		'orderby'        => 'name',
		'order'          => 'ASC',
		'limit'          => -1,
		'hide_invisible' => 0,
		'show_updated'   => 0,
	);

	$bookmarks = get_bookmarks($args);

	$widget  = '<ul class="where-is-my-blogroll-feed">';

	foreach ( $bookmarks as $b ) {

		if( $b->link_rss ) : // If link has rss

			$widget .= sprintf(
				'<li><h3><a href="%s" title="%s" target="_blank">%s</a></h3><ul>%s</ul></li>',
				esc_url($b->link_url), // link url
				esc_html($b->link_name), // link title
				esc_html($b->link_name), // link name
				wimb_read_RSS($b->link_rss) // rss feed for link
			);

		endif;

	}

	$widget .= '</ul>';

	$widget .= sprintf(
		'<a href="%s" title="%s" class="button button-default">%s</a>',
		'/wp-admin/link-manager.php', // Button link
		__('Links hinzuf&uuml;gen oder bearbeiten', 'wimb'), // Button title
		__('Konfigurieren', 'wimb') // Button text
	);

	echo $widget;

	die();
}

/**
 * Admin enqueue
 */
add_action( 'admin_enqueue_scripts', 'wimb_admin_enqueue_scripts' );
function wimb_admin_enqueue_scripts($hook)
{
    if ( 'index.php' != $hook )
		return;

	wp_enqueue_script(
		'wimb-ajax',
		plugins_url( '/js/wimb-ajax.js', __FILE__ ),
		array( 'jquery' ),
		'1.0',
		true
	);

	wp_localize_script(
		'wimb-ajax',
		'wimb_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		)
	);
}
