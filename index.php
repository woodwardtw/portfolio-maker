<?php 
/*
Plugin Name: Portfolio Maker
Plugin URI:  https://github.com/
Description: Let's make a portfolio and docx to posts
Version:     1.0
Author:      Tom Woodward
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: opened-duplicator

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


add_action('wp_enqueue_scripts', 'opened_duplicator_scripts');

function opened_duplicator_scripts() {                           
    $deps = array('jquery');
    $version= '1.0'; 
    $in_footer = true;    
    wp_enqueue_script('opened-dup-main-js', plugin_dir_url( __FILE__) . 'js/opened-dup-main.js', $deps, $version, $in_footer); 
    wp_enqueue_style( 'opened-dup-main-css', plugin_dir_url( __FILE__) . 'css/opened-dup-main.css');
}

add_action( 'gform_after_submission_1', 'gform_site_cloner', 10, 2 );//specific to the gravity form id

function gform_site_cloner($entry, $form){
    $_POST =  [
          'action'         => 'process',
          'clone_mode'     => 'core',
          'source_id'      => rgar( $entry, '1' ), //specific to the form entry fields and should resolve to the ID site to copy
          'target_name'    => rgar( $entry, '3' ), //specific to the form entry fields - need to parallel site url restrictions URL/DOMAIN
          'target_title'   => rgar( $entry, '2' ), //specific to the form entry fields TITLE
          'disable_addons' => true,
          'clone_nonce'    => wp_create_nonce('ns_cloner')
      ];
    
    // Setup clone process and run it.
    $ns_site_cloner = new ns_cloner();
    $ns_site_cloner->process();

    $site_id = $ns_site_cloner->target_id;
    $site_info = get_blog_details( $site_id );
    if ( $site_info ) {
     // Clone successful!
    }
}

//add created sites to cloner posts
add_action( 'gform_after_submission_1', 'gform_new_site_to_acf', 10, 2 );//specific to the gravity form id

