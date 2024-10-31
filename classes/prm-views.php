<?php

class prm_View{
    public static function bread_crumb($event_ID){
        //not in use for the time being
        /*include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
        $event = new prm_Event($event_ID);
        $name = $event->get_name();
        $bread_crumb = '<a href="'.PRM_ADMIN_URL.'prm-events">Events</a> > <a href="'.PRM_ADMIN_URL.'prm-events&m=c&action=view&i='.$event_ID.'" >'.$name.'</a>';

        return $bread_crumb;*/

    }
    public static function get_name($object,$object_id){
        global $wpdb;
        $sigular_object = ($object != 'series') ? rtrim($object,'s') : $object;
        // for tables with names concat first and last into name
        switch($sigular_object){
            case 'crew':
                $sql ='select concat_ws(" ",first_name,last_name) as name from '.$wpdb->prefix.'prm_'.$object.' where ID = '.$object_id;
                break;
            case 'competitor': // change when renaming ID to object_ID
                $sql ='select concat_ws(" ",first_name,last_name) as name from '.$wpdb->prefix.'prm_'.$object.' where competitors_ID = '.$object_id;
                break;
            default:
                $sql ='select name from '.$wpdb->prefix.'prm_'.$object.' where ID = '.$object_id;
        }

        $row = $wpdb->get_row($sql);
        if(!is_null($row)){
            return $row->name;
        }else return NULL;
    }
    /*
     * display license info etc at top of screen
     */
    public static function get_header(){
        $limit = '';
        if(PRM_PACK == 'jackie'){  //display number of comps left
            include_once(PRM_PUGIN_PATH.'classes/prm-pack.php');
            $comps = prm_Pack::get_comps();
            $limit = '<BR>Basic is limited to '.PRM_MAX_COMPS.' competitors, you have listed '.$comps.'.';
        }
        echo '<span style="float: right; color: orange;">Pedal On Race Management '.PRM_NAME .' version.
              <a href="http://pedalon.com.au/race-management">Support & Upgrades</a>'. $limit .'</span>';

    }
    /*
     * msg needs to be msg ID
     * object = racers:
     * we need to hand in the where for sql and events_ID if listing racers by event or else the races_ID
     * if listing racers for a race
     */
    public static function object_list($object,$updated_ID=NULL,$msg=NULL,$event_ID=NULL){
        self::get_header();
        if(!is_null($msg)){ //display message first if applicable
            include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
            echo prm_Help::get_msg($msg);
        }
        if($object == 'racers'){ // racers list calls need to be done in racers!
            die('use racers to call!');
        }
        $object_name = 'prm_'.ucfirst($object).'_List';
        //echo '<h2>'. $name .' Management</h2>';
        //create list
        include_once(PRM_PUGIN_PATH.'/classes/prm-list-'. $object .'.php');
        $list = new $object_name($updated_ID,$event_ID);
        $list->prepare_items();
        $list->display();
    }
    /*
     * displays any table row
     * to display (error)  msg hand-in prm_help->ID
     * if msg != NULL display message by ID
     * when dealing with prm-events page event_ID should be passed
     */
    public static function view($page,$object,$ID,$event_ID=NULL,$msg=NULL,$from=NULL){

        global $wpdb;
        $set_event_ID = (!empty($event_ID))? 'e='.$event_ID.'&' : ''; //add to quesrstring if set
        if($object != 'series'){ //don't trim s of Series
            $object_sigular = rtrim($object,'s'); //object singular
        }
        if($object == 'racers'){ // we need to join the racer and competitor table row so all fields can be reviewed
            $sql = 'select r.*,c.* from '.$wpdb->prefix.'prm_racers r left join '. $wpdb->prefix. 'prm_competitors c
                    on r.competitors_ID = c.competitors_ID
                    where r.ID = '.$ID;
        }elseif($object == 'competitors'){ // this needs to be fixed to set ID by object name
            $sql = 'select * from '.$wpdb->prefix.'prm_'.$object .' where competitors_ID = '.$ID;
        } else $sql = 'select * from '.$wpdb->prefix.'prm_'.$object .' where ID = '.$ID;

        $row = $wpdb->get_row($sql);
        echo self::bread_crumb($event_ID);
        self::get_header();
        echo '<h2>'. ucfirst($object_sigular) .' Details</h2>'; // screen name
        // display msg if set
        if(!is_null($msg)){ // we have a msg to display
            include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
            echo prm_Help::get_msg($msg,'before');
        }
        echo '<table>';
        foreach($row as $key => $value){ //iterate through fields
            //clean $key for display
            $display_key = ucfirst($key);
            $display_key = str_replace('_',' ',$display_key);
            //check if this is a system var, and if so don't show
            $action = (strpos($key,'_sys'))? 'system' : $key;
            switch($action){
                // list all fields to be ignored
                // for Pro+
                case 'offers_ID':
                case 'teams_ID':
                case 'max_team_size':
                case 'system': // skip, system var
                    $key_type = 'system';
                    break;
                case 'status':
                    $key_type = 'status';
                    break;
                default:
                    if(strpos($key,'_ID')){
                        $key_type = 'display_name';
                    }elseif(strpos($key,'_type')) { $key_type = 'type';
                    }else { $key_type = $key; }
            }
            switch($key_type){
                case 'system': // do nothing
                    break;
                case 'display_name': // get name and display instead of the ID
                    $display_key = substr($key,0,strpos($key,'_ID')); // remove _ID
                    $name = self::get_name($display_key,$value);
                    $display_key = ucfirst(str_replace('_',' ',$display_key));

                    if($display_key == 'Crew') $display_key = 'Director'; // change Crew to Director
                    $display_key = rtrim($display_key,'s');
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $name.' (id:'.$value .')</td></tr>';
                    break;
                case 'type':
                    $display_key = substr($key,0,strpos($key,'_type')); // remove _type
                    $display_key = ucfirst(str_replace('_',' ',$display_key));
                    if($key == 'competitors_type'){ // set a better key
                        $display_key = 'Competitors type';
                    }
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $value .'</td></tr>';
                    break;
                case 'status': // display light instead of number
                    require_once(PRM_PUGIN_PATH.'classes/prm-'.$object.'.php');
                    $class = 'prm_'. ucfirst($object);
                    if($object != 'races')$class = rtrim($class,'s');
                    $my_obj = new $class($row->ID);
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $my_obj->status() .'</td></tr>';
                    break;
                case 'racers_notes':
                    if($object == 'racers'){ // since we use a join here put a separator between race and comp details
                        echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $value .'</td></tr>';
                        echo '<tr><td colspan="2"><h3>Competitor Details</h3></td></tr>';

                    }
                    break;
                default:
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $value .'</td></tr>';
            }
        }
        if($object == 'settings'){ // we need to show current license with option to change
            echo '<tr><td><strong>Current License:</strong></td>';
            echo '<td>';
            echo PRM_NAME .' <a href="'. PRM_ADMIN_URL .$page. '&m=c&o=pack&a=license&i='. PRM_ORG_ID .'&f=vv" onclick="return confirm(\'Are you sure you want to install a new license?\');">Install new License</a>';
            echo '</td></tr>';
        }
        echo '</table>';
        if($object == 'racers'){
            include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
            $racer = new prm_Racer($ID);
            $races_ID = $racer->get_races_ID();
            //if we're coming from results list set a flag to return
            $return = ($from == '3m')? '&return=3' : '';
            echo '<a href="'. PRM_ADMIN_URL .$page. '&m=c&o=racers&a=list&i='. $races_ID .'&f=vv&where=races&type=entries'. $return .'"><img src="'. PRM_PLUGIN_URL .'images/OK.jpg" /></a>';
        }
        echo '<a href="'. PRM_ADMIN_URL .$page. '&m=m&o='. $object .'&a=edit&i='. $ID .'&'. $set_event_ID .'f=vv'. $return .'"><img src="'. PRM_PLUGIN_URL .'images/edit.jpg" /></a>';
        echo '<a href="'. PRM_ADMIN_URL .$page. '&m=m&o='. $object .'&a=del&i='. $ID .'&'. $set_event_ID .'f=vv'. $return .'" onclick="return confirm(\'Are you sure?\');"><img src="'. PRM_PLUGIN_URL .'images/delete.jpg" /></a>';


        if(!is_null($msg)){ // we have a msg to display
                    include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
                    echo '<BR><BR>'.prm_Help::get_msg($msg,'after');
                }
    }
}