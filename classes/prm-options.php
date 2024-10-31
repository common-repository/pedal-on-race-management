<?php
/**
 *
 */
class prm_Options{
    function __construct(){

    }
    /*
     * if $selected = '' set from table else choose selected option
     */
    public static function options($type,$key,$selected='',$came_from=NULL){
        global $wpdb;
        $table_name = $wpdb->prefix.'prm_options';
        $sql = 'select * from '.$table_name.' where type_sys = "'.$type.'" and key_option = "'.$key.'"';
        $options = $wpdb->get_results($sql);
        $output = '';
        foreach($options as $option){
            if($selected == '') $select =($option->selected == 2)? 'selected' : '';
            else $select = ($selected == $option->value)? 'selected':'';
            //if($selected == 'Casual') echo('v='.$option->value);
            $output .= '<option value="'. $option->value .'" '. $select .'>'. $option->value .'</option>';
            $exclude = ($option->exclude_sys == 2)? true : false; // exclude new option?
        }
        //add option to add a new record
        if(!$exclude){
            $output .= '<option onclick="location.href = \'?page='. $_REQUEST['page'] .'&m=m&o=options&a=add&f=ot&cf='. $came_from .'\';" value="new">New</option>';
        }
        return $output;
    }
    /*
     * this is to list out the names of a related table and list names in a drop down and ID in the value
     * object = racers etc
     * always returns name of object to add as option
     * object_id = the current ID selected, if one has been set
     * ---------
     * if the table is indeed a child than the parent object and ID need to be be passed and
     *   only the related records of the child table will be listed
     * eg if parent events and ID=1 than all races of event 1 will be returned as options
     * calling_obj = if set an onclick event to refresh screen and list the new options will be set
     * ---------
     * after drop down box is a link to add a new record
     */
    public static function table_options($object, $object_id=NULL,$parent_obj=NULL,$parent_id=NULL,$calling_obj=NULL){
        global $wpdb;

        $where ='';
        if(!is_null($parent_obj)){ // set where to restrict to child records
            if($object == 'competitors' && $parent_obj == 'races'){
                $where = ' where competitors_ID NOT IN (select competitors_ID from '.$wpdb->prefix.'prm_racers where races_ID = '. $parent_id .')';
            }else $where = ' where '.$parent_obj.'_ID = '.$parent_id;
        }
        // some special cases
        switch($object){ // change sql for different objects
            case 'crew':
                $sql = 'select ID, concat_ws(" ",first_name,last_name) as name from '.$wpdb->prefix .'prm_'.$object.$where;
                break;
            case 'competitors':
                $sql = 'select competitors_ID, concat_ws(" ",first_name,last_name) as name from '.$wpdb->prefix .'prm_'.$object.$where;
                break;
            case 'key_option':
                $sql = 'SELECT distinct key_option as name FROM '. $wpdb->prefix .'prm_options WHERE type_sys = "select" and exclude_sys = 1';
                break;

            default:
                $sql = 'select ID, name from '. $wpdb->prefix .'prm_'.$object.$where;

        }
        $options = $wpdb->get_results($sql);
        $output = '';
        foreach($options as $option){
            $onclick = ''; //ensure is blank unless set below
                if(!is_null($calling_obj)){ // if this is a parent reload page on click of a different parent
                    if($object == 'events'){
                        $onclick = 'onclick="location.href = \'?page='. $_REQUEST['page'] .'&m=m&o='. $calling_obj .'&a=add&e='. $option->ID .'&f=ot\';"';
                    }
                    if($object == 'races'){ //we'll need to set evnts_ID
                        $onclick = 'onclick="location.href = \'?page='. $_REQUEST['page'] .'&m=m&o='. $calling_obj .'&a=add&e='. $parent_id .'&races_ID='. $option->ID .'&f=ot\';"';
                    }
                }
            // set selected
            if(is_null($object_id)) $select = '';
            else{
                if($object == 'competitors'){ // fix when renaming primary key to object_ID
                    $select = ($object_id == $option->competitors_ID)? 'selected':'';
                }else $select = ($object_id == $option->ID)? 'selected':'';
            }
            // create option line
            if($object == 'key_option'){
                $output .= '<option '. $onclick .' value="'. $option->name .'" '. $select .'>'. $option->name .'</option>';
            }else{
                if($object == 'competitors')$output .= '<option '. $onclick .' value="'. $option->competitors_ID .'" '. $select .'>'. $option->name .'</option>';
                else $output .= '<option '. $onclick .' value="'. $option->ID .'" '. $select .'>'. $option->name .'</option>';
            }
        }
        //add option to add a new record
        //$page = 'prm-'.$object;

        $output .= '<option onclick="location.href = \'?page=prm-settings&m=m&o='. $object .'&a=add&f=ot\';" value="new">Add New</option>';
        return $output;
    }
    public static function get_setting($name){
        global $wpdb;
        $sql = 'select '.$name.' as setting from '.$wpdb->prefix.'prm_settings where ID = '. PRM_ORG_ID;
        $row = $wpdb->get_row($sql);
        return $row->setting;
    }
}
