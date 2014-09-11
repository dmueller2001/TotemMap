<?php
/*
Plugin Name: Totem Map Plugin
Plugin URI: None
Description: None
Authors: Nathan Heskia (email : nathan.heskia@utexas.edu) and Diane Mueller (discovertotems@gmail.com)
Version: 1.2
Author URI: www.discovertotems.com

License: GNU General Public License v2.0
License URI: http: //www.gnu.org/licenses/gpl-2.0.html



*/

$geodata = array();

function create_map() {
	wp_enqueue_style( 'leaflet-style', 'http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css', array(),'', 'all');
	
	wp_register_script('leaflet', 'http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js', array(),'', false );
	wp_enqueue_script('leaflet');

	wp_register_script( 'leaflet-prov', plugins_url( '_inc/leaflet-providers.js' , __FILE__ ), array(),'', false );
    wp_register_script( 'map-maker', plugins_url( '_inc/mapmaker.js' , __FILE__ ), array('jquery','leaflet','leaflet-prov'), '', true);
    
    global $geodata;
	create_posts_and_coordinates( $geodata );
	
	wp_localize_script( 'map-maker', 'geo', $geodata);
	wp_enqueue_script('map-maker');
}


add_action( 'wp_enqueue_scripts', 'create_map' );

function create_posts_and_coordinates( &$data ){
    global $wpdb;
	$content = $wpdb->get_results( 
		"
		SELECT ID, post_content, post_parent 
		FROM $wpdb->posts
		"
	);
	
	$meta_content = $wpdb->get_results(
		"
		SELECT DISTINCT post_id 
		FROM $wpdb->postmeta 
		WHERE meta_key = 'leaflet_lat'
		OR meta_key = 'leaflet_lng'
		"
	);
	
	$validposts = array();
	
	foreach ($meta_content as $val){
		array_push($validposts, $val->post_id);
	}
	
	foreach ($validposts as $id){
		$coordinates = get_post_coordinates($id);
		
		if($coordinates !== NULL){
			array_push($data, $coordinates);
		}
		
	}
}

function place_single_map( $content ){
	
	global $geodata;
	
	if( is_singular() && is_main_query() ){
		$post_id = $GLOBALS['post']->ID;
		$coordinates = get_post_coordinates($post_id);

		if ($coordinates !== NULL){
			wp_register_script( 'single-map-maker', plugins_url( '_inc/singlemapmaker.js' , __FILE__ ), array('jquery','leaflet','leaflet-prov'), '', true);
			wp_localize_script('single-map-maker', 'geo', $geodata);
			wp_localize_script('single-map-maker', 's_geo', $coordinates);
			wp_enqueue_script('single-map-maker');
		}
		return $content.'<div id="postmap"></div>';
	}
	else{
		return $content;
	}
	
}

add_filter('the_content','place_single_map');


function get_post_coordinates( $post_id ){
	global $wpdb;
	$lat = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'leaflet_lat'");
	$lng = $wpdb->get_row("SELECT * FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'leaflet_lng'");
		
	if ($lat !== NULL && $lng !== NULL){	
		$lat_format = preg_match('/^-?\d+\.\d+$/',$lat->meta_value);
		$lng_format = preg_match('/^-?\d+\.\d+$/',$lng->meta_value);

		if($lat_format && $lng_format){
				return array($post_id, $lat->meta_value, $lng->meta_value);
		}
		else{
				return NULL;
		}
	}
	
}

function add_leaflet_meta_box() {

	$screens = array( 'post', 'page' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'leaflet_meta_box',
			__( 'Leaflet Map Coordinates', 'leaflet_coordinates_fields' ),
			'leaflet_meta_callback',
			$screen
		);
	}
}

add_action( 'add_meta_boxes', 'add_leaflet_meta_box' );

function leaflet_meta_callback( $post ) {
	wp_nonce_field( 'leaflet_meta_box', 'leaflet_meta_box_nonce' );

	$leaflet_lat = get_post_meta( $post->ID, 'leaflet_lat', true );
	$leaflet_lng = get_post_meta( $post->ID, 'leaflet_lng', true );

	echo '<label for="leaflet_lat" style="font-weight: bold; padding-left: 15px">';
	_e( 'Latitude: ', 'leaflet_coordinates_fields' );
	echo '</label> ';
	echo '<input type="text" id="leaflet_lat" name="leaflet_lat" value="' . esc_attr( $leaflet_lat ) . '" size="25" /><br /><br />';

	echo '<label for="leaflet_lng" style="font-weight: bold; padding-left: 15px">';
	_e( 'Longitude: ', 'leaflet_coordinates_fields' );
	echo '</label> ';
	echo '<input type="text" id="leaflet_lng" name="leaflet_lng" value="' . esc_attr( $leaflet_lng ) . '" size="25" />';

}

function save_leaflet_data( $post_id ) {

	if ( ! isset( $_POST['leaflet_meta_box_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['leaflet_meta_box_nonce'], 'leaflet_meta_box' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	if ( ! isset( $_POST['leaflet_lat'] ) ) {
		return;
	}
	if ( ! isset( $_POST['leaflet_lng'] ) ) {
		return;
	}

	$lat = sanitize_text_field( $_POST['leaflet_lat'] );
	$lng = sanitize_text_field( $_POST['leaflet_lng'] );

	update_post_meta( $post_id, 'leaflet_lat', $lat );
	update_post_meta( $post_id, 'leaflet_lng', $lng );
}

add_action( 'save_post', 'save_leaflet_data' );
