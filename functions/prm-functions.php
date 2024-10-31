<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wolfskafte-zauss
 * Date: 23/10/14
 * Time: 12:00 PM
 * To change this template use File | Settings | File Templates.
 */
function prm_live_results($atts){
    // get vars from shortcode
    $a = shortcode_atts( array(
            'events_id' => NULL,
            'races_id' => NULL
        ), $atts );
    $events_id = $a['events_id'];
    $races_id = $a['races_id'];
    if(is_null($events_id)) echo 'You need to insert an events_id to see its live results, eg:<BR>
                                   [prm_live_results events_id="1"]';
    //die(var_dump($events_id));
    // set defines
    define('PRM_PUGIN_PATH',ABSPATH . 'wp-content/plugins/pedal-on-race-management/');
    define('PRM_PLUGIN_URL',get_option('siteurl').'/wp-content/plugins/pedal-on-race-management/');
    define('PRM_ORG_ID',1);
    //build race options

    //list live results
    include_once(ABSPATH.'wp-admin/includes/template.php');
    include_once(PRM_PUGIN_PATH.'front/prm-list-live-results.php');
    $live_results = new prm_Live_Results($races_id,'events',$events_id);
    $live_results->prepare_items();
    $live_results->display();
}
function prm_results($atts){
    // get vars from shortcode
    $a = shortcode_atts( array(
            'events_id' => NULL,
            'races_id' => NULL,
            'courses_id' => NULL
        ), $atts );
    $events_id = $a['events_id'];
    $races_id = $a['races_id'];
    $courses_id = $a['courses_id'];
    //lets give some feedback if required setting are not found
    if(is_null($events_id)) echo 'You need to insert an events_id and a course or race id to see its results, eg:<BR>
                                   [prm_results events_id="1" races_id="20"]';
    if(is_null($races_id) && is_null($courses_id)){ // need to have one value
        echo 'You need to set if you want to show results by course or race on this page eg:<BR>
              [prm_results events_id="1" course_id="2"]';
    }
    //die(var_dump($events_id));
    // set defines
    define('PRM_PUGIN_PATH',ABSPATH . 'wp-content/plugins/pedal-on-race-management/');
    define('PRM_PLUGIN_URL',get_option('siteurl').'/wp-content/plugins/pedal-on-race-management/');
    define('PRM_ORG_ID',1);

    //list results
    // set what to list by
    $list_for = (!is_null($races_id))? 'race' : 'course';
    //include files
    include_once(ABSPATH.'wp-admin/includes/template.php');
    include_once(PRM_PUGIN_PATH.'front/prm-results.php');
    //display results
    switch($list_for){
        case 'race':
            $results = new prm_Results($list_for,$races_id);
            break;
        case 'course':
            $results = new prm_Results($list_for,$courses_id);
            break;
    }
    $results->prepare_items();
    $results->display();
}
function register_shortcodes(){
  add_shortcode( 'prm_live_results', 'prm_live_results' );
  add_shortcode( 'prm_results', 'prm_results' );
}

add_action( 'init', 'register_shortcodes');