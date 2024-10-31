<?php  
/* 
Plugin Name: Pedal On Race Management
Plugin URI: http://pedalon.com.au/race-management
Description: Wordpress Plugin to organise and run racing events
Version: 1.1
Author: Wolf Skafte-Zauss
Author URI: http://pedalon.com.au
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

*/  
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 * This is the code to install prm on activation. If you remove prm you will need to remove
 * all tables manually (they all start with your WordPress prefix (wp_) and then prm_
 */

function prm_table_install() {
	//install the required tables required for PRM
   global $wpdb;
    // table awards
    $table_name = $wpdb->prefix . "prm_awards";
        if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
          ID int(11) NOT NULL AUTO_INCREMENT,
          events_ID int(11) NOT NULL,
          courses_ID int(11) NOT NULL,
          award_type varchar(25) NOT NULL DEFAULT "SEX",
          award_condition varchar(25) NOT NULL DEFAULT "FEMALE",
          award_levels tinyint(4) DEFAULT "3",
          over_age tinyint(4) DEFAULT NULL,
          under_age tinyint(4) DEFAULT NULL,
          description text,
          notes text,
          PRIMARY KEY (ID)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;';
        $result = $wpdb->get_var($sql);
    }
    // table bibs
    $table_name = $wpdb->prefix . "prm_bibs";
        if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
          ID int(11) NOT NULL AUTO_INCREMENT,
            bib_number varchar(10) NOT NULL DEFAULT "None",
            reserved tinyint(1) NOT NULL DEFAULT "1",
            competitors_ID int(11) DEFAULT NULL,
            crew_ID int(11) DEFAULT NULL,
            allocated_date datetime DEFAULT NULL,
            end_date date DEFAULT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY bib_number (bib_number)
          ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT="Permanent bibs"';
        $result = $wpdb->get_var($sql);
    }
    // table competitors
    $table_name = $wpdb->prefix . "prm_competitors";
        if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
          competitors_ID int(11) NOT NULL AUTO_INCREMENT,
            competitors_type varchar(25) NOT NULL DEFAULT "Casual" COMMENT "VIP competitors with permanent bibs are available in Pro+",
            first_name varchar(100) NOT NULL COMMENT "Required field",
            last_name varchar(100) DEFAULT NULL,
            gender varchar(10) NOT NULL COMMENT "Recommended - used for all gender based awards",
            email varchar(100) DEFAULT NULL COMMENT "Recommend - very useful to contact competitors for race updates",
            birth_date date DEFAULT NULL COMMENT "Recommended - used for all age based awards",
            medical_info text COMMENT "Any medical information the events organiser should be aware off",
            next_of_kind_first varchar(100) DEFAULT NULL COMMENT "Next of kind first name",
            next_of_kind_last varchar(100) DEFAULT NULL COMMENT "next of kind second name",
            next_of_kind_phone varchar(50) DEFAULT NULL,
            street varchar(100) DEFAULT NULL,
            town varchar(100) DEFAULT NULL,
            state varchar(25) DEFAULT "NSW",
            post_code varchar(10) DEFAULT NULL,
            permanent_bibs_ID int(11) DEFAULT NULL,
            competitor_notes text COMMENT "Internal notes for crew eyes only",
            PRIMARY KEY (competitors_ID),
            UNIQUE KEY email (email)
          ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

          ';
        $result = $wpdb->get_var($sql);
    }
    // table courses
        $table_name = $wpdb->prefix . "prm_courses";
        if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
          ID int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL COMMENT "Required field",
            short_name varchar(15) DEFAULT NULL COMMENT "Required field: short name for racing lists, max length 15 characters",
            course_usage_type varchar(50) DEFAULT NULL,
            length decimal(5,2) DEFAULT NULL,
            measurement_type varchar(10) DEFAULT NULL,
            fastest_expected_time time DEFAULT NULL COMMENT "used to check actual results for errors",
            slowest_expected_time time DEFAULT NULL,
            access text,
            facilities text,
            street varchar(100) DEFAULT NULL,
            town varchar(100) DEFAULT NULL,
            state varchar(100) DEFAULT NULL,
            post_code varchar(10) DEFAULT NULL,
            map_refrence varchar(100) DEFAULT NULL,
            description text,
            notes text,
            PRIMARY KEY (ID),
            UNIQUE KEY name (name)
          ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;';
        $result = $wpdb->get_var($sql);
    }
    // table crew
    $table_name = $wpdb->prefix . "prm_crew";
        if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
          ID int(11) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) DEFAULT NULL,
            email varchar(100) DEFAULT NULL COMMENT "Can be blank, but if entered must be unique to this crew member",
            phone varchar(50) DEFAULT NULL,
            function_type varchar(25) DEFAULT NULL,
            first_aid_type varchar(25) NOT NULL,
            notes text COMMENT "Good place to list skills. likes and dislikes etc",
            PRIMARY KEY (ID),
            UNIQUE KEY email (email)
          ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;';
        $result = $wpdb->get_var($sql);
    }
    // table crew_rego_event
    $table_name = $wpdb->prefix . "prm_crew_rego_event";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                crew_ID int(11) NOT NULL,
                events_ID int(11) NOT NULL,
                rego_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                role varchar(50) DEFAULT NULL,
                notes text,
                PRIMARY KEY (ID)
              ) ENGINE=MyISAM DEFAULT CHARSET=latin1;';
            $result = $wpdb->get_var($sql);
    }
    // table events
    $table_name = $wpdb->prefix . "prm_events";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                series_ID int(11) NOT NULL COMMENT "Repeating series of events this event belongs to.",
                settings_ID int(11) NOT NULL DEFAULT "1",
                crew_ID int(11) NOT NULL COMMENT "Main event co-ordinator",
                event_type varchar(10) NOT NULL DEFAULT "RACE" COMMENT "RACE is the only option at this stage, other options might be added at a later date.",
                name varchar(100) NOT NULL,
                status tinyint(4) NOT NULL DEFAULT "0",
                date_from date DEFAULT NULL COMMENT "Date your event starts on. If your event happens on a single date than start and to are the same date.",
                date_to date DEFAULT NULL,
                description text COMMENT "event description for website display, Pro+ only",
                notes text COMMENT "Internal notes for crew eyes only",
                PRIMARY KEY (ID),
                UNIQUE KEY name (name)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;';
            $result = $wpdb->get_var($sql);
    }
    // table exceptions
    $table_name = $wpdb->prefix . "prm_exceptions";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                course_ID int(11) NOT NULL,
                bib int(5) NOT NULL,
                sections_ID int(11) NOT NULL,
                crew_ID int(11) DEFAULT NULL,
                type varchar(25) NOT NULL DEFAULT "TIME",
                time datetime DEFAULT NULL,
                other varchar(100) DEFAULT NULL,
                description text,
                PRIMARY KEY (ID)
              ) ENGINE=MyISAM DEFAULT CHARSET=latin1;';
            $result = $wpdb->get_var($sql);
    }
    // table help
    $table_name = $wpdb->prefix . "prm_help";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                class varchar(50) NOT NULL,
                method varchar(50) NOT NULL,
                position varchar(50) DEFAULT "after",
                type varchar(25) NOT NULL DEFAULT "info" COMMENT "info, error or success",
                message text NOT NULL,
                PRIMARY KEY (ID)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;';
            $result = $wpdb->get_var($sql);
        //insert data
        $sql = '
        INSERT INTO '. $table_name .' (ID, class, method, position, type, message) VALUES
        (1, "model", "del", "before", "error", "This record can not be deleted because it is being referenced in other tables. You will need to delete all other records that mention this record first."),
        (2, "racers", "add", "before", "info", "Choose a competitor from the list below to add to your event or click Add New Competitor. "),
        (3, "all", "add", "before", "info", "The * indicates a field that is recommend for the normal functioning of PRM."),
        (4, "racers", "choose_course", "before", "info", "Please choose the course to enter the competitor into below, click [Enter Competitor] under relevant race."),
        (5, "racers", "enter_competitor", "before", "info", "Please click [Enter] if the competitor is listed, else click [Add new Racer] at the top to add a new competitor to your list.<BR>The list is sorted by First name, click Last name to sort by Last name instead."),
        (6, "racers", "update", "before", "error", "The bib you""ve entered is already in use, please use a different bib number."),
        (7, "model", "update", "before", "success", "Your data was successfully updated."),
        (8, "model", "del", "before", "success", "The record was successfully deleted."),
        (9, "views", "view", "after", "info", "Bibs Type is if you want to use permanent bibs for competitors or not.\r\nMax bibs is related to permanent bibs and is the highest bib number that can be assigned as a permanent bib."),
        (10, "model", "insert", "before", "error", "You have listed the maximum number of competitors the Basic version allows. If you need to add further competitors you need to delete some existing ones or upgrade to the Clubs version, which allows for unlimited competitors to be listed. <a href="http://pedalon.com.au/race-management">You can upgrade your license here</a>"),
        (11, "model", "update", "before", "error", "An error occurred while inserting or updating the data in the database. Please try again. If the error persists please contact support."),
        (12, "model", "update", "before", "error", "You""re attempting to update or insert a record with a duplicate name."),
        (13, "events", "open_entries", "before", "success", "Success, the event has now been opened for entries."),
        (14, "events", "close_entries", "before", "success", "The event has now been closed for entries."),
        (15, "events", "close_event", "before", "error", "The event could not be closed because not all races of this event have been closed."),
        (16, "model", "update", "before", "info", "It seems that you didn""t make any changes to any of the fields, so no change was made to the record on file."),
        (17, "races", "close_race", "before", "error", "The race could not be closed because there are still competitors on course racing it seems. Please click On Course for this race so see who is still racing."),
        (18, "model", "update", "before", "error", "The record was updated <strong>but you have not assigned a bib number!</strong>"),
        (19, "racers", "sign-in", "before", "error", "This competitor has not been assigned a bib number! Use View > Edit and assign a bib number and than sign them in."),
        (20, "races", "publish", "before", "success", "The results for this race have now been published."),
        (21, "model", "insert", "before", "error", "Please try again and be sure to give the course a name and a short name!");
        ';
        $result = $wpdb->get_var($sql);
    }
    // table options
    $table_name = $wpdb->prefix . "prm_options";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                type_sys varchar(25) NOT NULL DEFAULT "select",
                key_option varchar(25) NOT NULL COMMENT "Be sure to select the proper key you want to add a new option for.",
                value varchar(25) NOT NULL COMMENT "This is the actual value you will see in the drop down, eg CA in state.",
                selected tinyint(1) NOT NULL DEFAULT "1" COMMENT "2=display as default value to be selected",
                exclude_sys tinyint(4) NOT NULL DEFAULT "1" COMMENT "2 = new items can""t be added",
                PRIMARY KEY (ID)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=64 ;';
            $result = $wpdb->get_var($sql);
        //insert data
        $sql = '
        INSERT INTO '. $table_name .' (ID, type_sys, key_option, value, selected, exclude_sys) VALUES
        (1, "select", "awards_type", "Sex", 1, 1),
        (2, "select", "awards_type", "Age", 1, 1),
        (3, "select", "awards_type", "PB", 1, 1),
        (4, "select", "competitors_type", "VIP", 1, 2),
        (5, "select", "competitors_type", "Casual", 2, 2),
        (6, "select", "course_usage_type", "Triathlon", 1, 1),
        (7, "select", "course_usage_type", "Duathlon", 1, 1),
        (8, "select", "course_usage_type", "Run", 1, 1),
        (9, "select", "course_usage_type", "Adventure Race", 1, 1),
        (10, "select", "course_usage_type", "Sailing", 1, 1),
        (11, "select", "course_usage_type", "Car Race", 1, 1),
        (12, "select", "gender", "Female", 2, 2),
        (13, "select", "gender", "Male", 1, 2),
        (14, "select", "state", "NSW", 2, 1),
        (15, "select", "state", "QLD", 1, 1),
        (16, "select", "state", "VIC", 1, 1),
        (17, "select", "state", "SA", 1, 1),
        (18, "select", "state", "NT", 1, 1),
        (19, "select", "state", "WA", 1, 1),
        (20, "select", "state", "TAS", 1, 1),
        (21, "select", "result_type", "FIN", 1, 1),
        (22, "select", "result_type", "DNF", 1, 1),
        (23, "select", "result_type", "DQ", 1, 1),
        (24, "select", "result_type", "N/A", 2, 1),
        (25, "select", "event_type", "Race", 2, 2),
        (26, "select", "interval_type", "Annual", 2, 1),
        (27, "select", "interval_type", "Monthly", 1, 1),
        (28, "select", "interval_type", "Bi-Annual", 1, 1),
        (29, "select", "first_aid_type", "None", 2, 1),
        (30, "select", "first_aid_type", "Current", 1, 1),
        (31, "select", "first_aid_type", "Not Current", 1, 1),
        (32, "select", "function_type", "Marshal", 2, 1),
        (33, "select", "function_type", "Timing Official", 1, 1),
        (34, "select", "function_type", "Admin", 1, 1),
        (35, "select", "function_type", "Management", 1, 1),
        (36, "select", "leg_type", "XC Run", 1, 1),
        (37, "select", "leg_type", "MTB Ride", 1, 1),
        (38, "select", "leg_type", "Swim", 1, 1),
        (39, "select", "leg_type", "Road Run", 1, 1),
        (40, "select", "leg_type", "Paddle", 1, 1),
        (41, "select", "leg_type", "Horse Ride", 1, 1),
        (42, "select", "measurement_type", "Km", 2, 1),
        (43, "select", "measurement_type", "meters", 1, 1),
        (44, "select", "measurement_type", "Miles", 1, 1),
        (45, "select", "bibs_type", "Yes", 2, 2),
        (46, "select", "bibs_type", "No", 1, 2),
        (47, "select", "timing_type", "Manual", 1, 2),
        (48, "select", "timing_type", "Simple", 2, 2),
        (49, "select", "entries_open_type", "No", 2, 2),
        (50, "select", "entries_open_type", "Yes", 1, 2),
        (51, "select", "date_format_type", "d/m/Y", 2, 1),
        (52, "select", "date_format_type", "Y-m-d", 1, 1),
        (53, "select", "date_format_type", "m-d-Y", 1, 1),
        (57, "select", "state", "VEE", 1, 1),
        (56, "select", "state", "CAL", 1, 1),
        (58, "select", "state", "VVV", 1, 1),
        (59, "select", "state", "ddd", 1, 1),
        (60, "select", "state", "ccc", 1, 1),
        (61, "select", "state", "aaa", 1, 1),
        (62, "select", "debug_type", "No", 2, 2),
        (63, "select", "debug_type", "Yes", 1, 2);
        ';
        $result = $wpdb->get_var($sql);
    }
    // table racers
    $table_name = $wpdb->prefix . "prm_racers";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                events_ID int(11) NOT NULL COMMENT "Switch to a different event and its races will be listed below.",
                races_ID int(11) NOT NULL COMMENT "This lists all races for above event.",
                competitors_ID int(11) NOT NULL COMMENT "This list contains only competitors who have not entered the above race.",
                offers_ID int(11) DEFAULT NULL,
                teams_ID int(11) DEFAULT NULL,
                status tinyint(4) NOT NULL DEFAULT "0",
                race_kit_prepared varchar(5) NOT NULL DEFAULT "NO" COMMENT "If you prepare race kits before race day you can use this field to ensure all have been done",
                bib_number int(6) DEFAULT NULL COMMENT "Enter bib number for this racer, PRM will check if this bib is already in use when you click [Save].",
                start_sys datetime DEFAULT NULL COMMENT "used to record start for smart matts etc",
                finish_sys datetime DEFAULT NULL COMMENT "contains the race finish time stamp",
                result_type varchar(25) DEFAULT NULL,
                race_time time DEFAULT NULL,
                race_placing smallint(6) DEFAULT NULL,
                course_placing smallint(6) DEFAULT NULL,
                message text COMMENT "Any message for a competitor to be given at Rego or Sign-up.",
                racers_notes text COMMENT "Internal notes for crew eyes only",
                PRIMARY KEY (ID),
                UNIQUE KEY ix_competitors (events_ID,races_ID,competitors_ID),
                UNIQUE KEY bib_number (bib_number)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;';
            $result = $wpdb->get_var($sql);
    }
    // table races
    $table_name = $wpdb->prefix . "prm_races";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                events_ID int(11) NOT NULL,
                courses_ID int(11) NOT NULL,
                crew_ID int(11) DEFAULT NULL COMMENT "Race Director",
                name varchar(100) NOT NULL COMMENT "Required field - eg. Female Ultra Race, often the same year to year",
                race_date_time datetime DEFAULT NULL COMMENT "Recommend - yyyy-mm-dd hh:mm (Start date & time of this race)",
                setup_status_sys tinyint(1) NOT NULL DEFAULT "1",
                status tinyint(1) NOT NULL DEFAULT "1",
                max_entries int(11) DEFAULT NULL COMMENT "Max number of entries available for this race.",
                max_team_size int(11) DEFAULT NULL,
                timing_type varchar(25) NOT NULL DEFAULT "Simple",
                started_sys datetime DEFAULT NULL COMMENT "actual race start time",
                results_sys tinyint(1) NOT NULL DEFAULT "1" COMMENT "1 = default, 3 = placings, 5 = published",
                description text COMMENT "for display on website, Pro+ only",
                notes text COMMENT "Internal notes for crew eyes only",
                PRIMARY KEY (ID)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;';
            $result = $wpdb->get_var($sql);
    }
    // table series
    $table_name = $wpdb->prefix . "prm_series";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                interval_type varchar(25) NOT NULL,
                PRIMARY KEY (ID),
                UNIQUE KEY name (name)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;';
            $result = $wpdb->get_var($sql);
    }
    // table settings
    $table_name = $wpdb->prefix . "prm_settings";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
    	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
              ID int(11) NOT NULL AUTO_INCREMENT,
                name varchar(100) DEFAULT NULL COMMENT "Your organisation""s name",
                bibs_type varchar(10) NOT NULL DEFAULT "Yes" COMMENT "Do you want to issue permanet bibs",
                max_bib_number int(5) DEFAULT "250" COMMENT "Recommended - the maximum bib number for permanent bibs",
                date_format_type varchar(10) NOT NULL DEFAULT "d/m/Y",
                license_sys varchar(100) DEFAULT NULL,
                model_sys varchar(25) DEFAULT NULL,
                max_comps_sys int(2) DEFAULT "25",
                debug_type varchar(3) NOT NULL DEFAULT "No",
                PRIMARY KEY (ID)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
              ';
            $result = $wpdb->get_var($sql);
        //insert data
        $sql = '
        INSERT INTO '. $table_name .' (ID, name, bibs_type, max_bib_number, date_format_type, license_sys, model_sys, max_comps_sys, debug_type) VALUES
        (1, "Your organisations name", "Yes", 250, "d/m/Y", NULL, "basic", 25, "No");
        ';
        $result = $wpdb->get_var($sql);
    }
}
register_activation_hook(__FILE__,'prm_table_install');



/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 * All defines are here
 */

define('PRM_PUGIN_PATH',ABSPATH . 'wp-content/plugins/pedal-on-race-management/');
define('PRM_ADMIN_URL',admin_url().'admin.php?page=');
define('PRM_PLUGIN_URL',get_option('siteurl').'/wp-content/plugins/pedal-on-race-management/');
define('PRM_ORG_ID',1);
// set pack level
include_once(PRM_PUGIN_PATH.'classes/prm-pack.php');
$pack = new prm_Pack();
define('PRM_PACK',$pack->get_member());
define('PRM_MAX_COMPS',$pack->get_max_comps());
define('PRM_NAME',$pack->get_name());
define('PRM_PACK_POSI',$pack->get_posi());
include_once(PRM_PUGIN_PATH.'classes/prm-options.php');
define('PRM_DATE_FORMAT',prm_Options::get_setting('date_format_type'));
define('PRM_DEBUG',prm_Options::get_setting('debug_type'));
include_once PRM_PUGIN_PATH.'classes/prm_admin.php'; //prm admin controls
?>