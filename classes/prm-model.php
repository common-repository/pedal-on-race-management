<?php
/**
 *
 */
class prm_Model{
    var $events_ID;
    var $races_ID;
    /*
     * display fields of object $table
     * for racers these special functionality applies:
     *   -> hand in events_ID or races_ID
     *   -> event can be NULL in which case all open events are loaded and the first is ued to populate races
     *   -> this will ensure events -> races -> competitors
     * object = the table we want to add a record to
     * list_by = event or race
     * list_by_id = events_ID or races_ID, depending on above
     */
    public static function add($page,$object,$events_ID=NULL, $races_ID=NULL){

        if(PRM_PACK == 'jackie' && $object == 'competitors'){ //check if max_comps has been reached
            include_once(PRM_PUGIN_PATH.'classes/prm-pack.php');
            $comps = prm_Pack::get_comps();
            if($comps >= PRM_MAX_COMPS){
                include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
                prm_View::object_list('competitors',NULL,10);
                exit;
            }
        }
        global $wpdb;
        include_once(PRM_PUGIN_PATH.'classes/prm-options.php');
        $object_sigular = rtrim($object,'s');//table in singular
        $sql = 'show full columns from '. $wpdb->prefix.'prm_'.$object;
        $columns = $wpdb->get_results($sql);
        echo '<form action="?page='. $page .'&m=m&o='. $object .'&a=insert&e='. $events_ID .'&f=ma" method="post">';
        //echo '<form action="?page='. $page .'&m=m&o='. $object .'&a=insert&f=ma" method="post">';

        // get events name to add to header for racers
        if($object == 'racers'){
            include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
            $my_event = new prm_Event($events_ID);
            echo '<h2>Add '. ucfirst($object_sigular) .' to: '. $my_event->get_name() .'</h2>';
        } else echo '<h2>Add '. ucfirst($object_sigular) .'</h2>';
        foreach($columns as $column){
            //test for special fields that need attention
            //fields that are not relevant at stage of adding a new record
            if($column->Field == 'result_type' && $object == 'racers') $special = 'ignore';
            elseif($column->Key == 'PRI') $special = 'ID'; //primary key, do not list
            elseif(strpos($column->Field,'_type')) $special = 'type';
            elseif(strpos($column->Field,'_sys')) $special = 'system';
            elseif($column->Type == 'date') $special = 'date';
            elseif($column->Type == 'datetime') $special = 'datetime';
            elseif($column->Type == 'time') $special = 'time';
            elseif($column->Type == 'text') $special = 'text';


            // until v1.1
            elseif($column->Field == 'settings_ID') $special = 'ID'; //ignore for the moment
            elseif($column->Field == 'permanent_bibs_ID') $special = 'ID'; // feature for Pro+
            elseif($column->Field == 'offers_ID') $special = 'ID'; // feature for Pro+
            elseif($column->Field == 'teams_ID') $special = 'ID'; // feature for Pro+

            elseif(strpos($column->Field,'_ID')) $special = 'list_ID'; // this is a foreign key
            elseif($column->Field == 'key_option') $special = 'list_ID'; // need to list option types
            else $special = $column->Field;
            /*
             * relationships between tables when adding a new record works as follows
             * 1. in the table the parent must be listed before the child (eg events before races
             * 2. When creating the adding screen the child drop down will contain the records related to the parent
             *    eg if event = 1 then all races where event = 1 will be listed
             * This is achieved by passing the parent_obj and id with very child to options
             */
            // set parent_obj and id to null
            $parent_obj = NULL;
            $parent_id = NULL;
            switch($special){
                case 'ignore': // not required at this stage of adding a new record
                case 'system': //ignore since for system update only
                case 'ID':
                case 'offers_ID':
                case 'events_ID':
                case 'courses_ID':
                case 'max_team_size';
                //case 'competitors_ID':
                case 'status': // auto updated by PRM only
                    //do nothing - will be set during insert
                break;
                case 'date':
                    $display_key = ucfirst(str_replace('_',' ',$column->Field)); //replace '-' with space and capitalise
                    $display_key .= ' (yyyy-mm-dd)';
                    echo '<p><label for="'. $column->Field .'"><strong>'. $display_key .':</strong> <input type="text" name="'. $column->Field .'" ></label>  '. $column->Comment .'</p>';
                break;
                case 'text': //text areas
                    $display_key = ucfirst(str_replace('_',' ',$column->Field)); //replace '-' with space and capitalise
                    $text = '<p><label for="'. $column->Field .'"><strong>'. $display_key .'</strong> ('. $column->Comment .'): <br><textarea cols="60" type="text" name="'. $column->Field .'"></textarea></label></p>';
                    echo $text;
                break;
                case 'state':
                case 'gender':
                case 'type': //need to generate select list
                    $display_key = ucfirst(str_replace('_',' ',$column->Field)); //replace '-' with space and capitalise
                    $options = '<p><label for="'. $column->Field .'"><strong>'. $display_key .':</strong> <select name="'. $column->Field .'">';
                    $options .= prm_Options::options('select',$column->Field);
                    $options .= '</select></label>  '. $column->Comment .'</p>';
                    echo $options;
                break;
                case 'list_ID': // need to build options from relevant table
                    //set vars to ensure they're NULL unless set
                    $target_object = NULL;
                    $target_object_id = NULL;
                    $calling_obj = NULL;

                    $target_object = rtrim($column->Field,'_ID'); // strip _ID from field to get target object name
                    if($object == 'racers'){// we need to list races dependant on events
                        switch($target_object){
                            case 'events':
                                if($races_ID > 0){ //we need to set events_ID based on the races_ID
                                    include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
                                    $target_object_id = prm_Races::get_var('events_ID',$races_ID);
                                    $calling_obj = $object;
                                }else{ // we need to get options by events_ID passed and set races_ID
                                    if(!is_null($events_ID)){
                                        $target_object_id = $events_ID;
                                        $calling_obj = $object;
                                    }
                                    else{ // if no events_ID has been passed choose and

                                    }
                                }
                                break;
                            case 'races': // we need to set parent to select only children for this parent event
                                if(is_null($races_ID)){ // we need to nomiate a race to use
                                    include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
                                    $races_ID = prm_Races::get_id($events_ID);
                                }
                                $target_object_id = $races_ID;
                                $parent_obj = 'events';
                                $parent_id = $events_ID;
                                $calling_obj = $object;
                                break;
                            case 'competitors':
                                $parent_obj = 'races';

                                $parent_id = $races_ID;
                                break;
                        }
                    }
                    if($target_object == 'events'){ // ensure that current event is selected in drop down
                        $target_object_id = $events_ID;
                    }

                    if($target_object == 'events'){ // don't list events, since this is confusing

                    }else{

                        $display_key = ucfirst(str_replace('_',' ',$target_object)); //replace '-' with space and capitalise
                        $display_key = rtrim($display_key,'s'); // change to sigular since most keys are plural
                        $options = '<p><label for="'. $column->Field .'"><strong>'. $display_key .':</strong> <select name="'. $column->Field .'">';
                        $options .= prm_Options::table_options($target_object,$target_object_id,$parent_obj,$parent_id,$calling_obj);
                        $options .= '</select></label>  '. $column->Comment .'</p>';

                        echo $options;
                    }
                break;
                default:
                    $display_key = ucfirst($column->Field); //make 1st letter upper case
                    $display_key = str_replace('_',' ',$display_key); //replace '-' with space
                    echo '<p><label for="'. $column->Field .'"><strong>'. $display_key .':</strong> <input type="text" value= "'. $column->Default .'" name="'. $column->Field .'" ></label>  '. $column->Comment .'</p>';
            }
        }
        switch($object){
            case 'competitors':
            case 'events':
            case 'courses':
            case 'settings':
            case 'crew':
            case 'series':
                //only include events ID if object is child of events
                break;
            default:
                echo '<input name="events_ID" type="hidden" value="'. $events_ID .'">';
                break;
        }
        echo '<p><input type="image" src="'. PRM_PLUGIN_URL .'images/save.jpg" alt="Update '. $object_sigular .'"></p>';
        echo '</form>';
    }

    public static function bread_crumb($event_ID,$t){

        include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
        $event = new prm_Events($event_ID);
        $name = $event->get_name();
        $bread_crumb = '<a href="'.PRM_ADMIN_URL.'prm-events">Events</a> > <a href="'.PRM_ADMIN_URL.'prm-events&action=view&event='.$event_ID.'" >'.$name.'</a>';

        return $bread_crumb;

    }

    public static function del($object,$del_ID,$event_ID){
        global $wpdb;
        //first we need to check if a related record exists -> check if it's ID is in any other table
        $object_ID = $object.'_ID';
        $sql = 'SELECT DISTINCT TABLE_NAME as table_name
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE COLUMN_NAME IN ("'. $object_ID .'")';
        $tables = $wpdb->get_results($sql);
        if($tables > 0){ //if true our key exists in another table, we need to check if the ID exixts
            $count = 0;
            foreach($tables as $table){
                $sql = 'select 1 from '.$table->table_name.' where '.$object_ID.' = '.$del_ID;
                $result = $wpdb->get_row($sql);
                if(!is_null($result)) $count++;
            }
            if($count > 0){ //we have at least one table that has this ID
                include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
                prm_View::object_list($object,$del_ID,1,$event_ID); // list object with msg
            }else{// safe to delete
                $wpdb->delete($wpdb->prefix.'prm_'.$object,array('ID' => $del_ID));
                include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
                $object = ($object == 'racers')? 'races' : $object; // after remove of comp from race return to race central
                prm_View::object_list($object,NULL,8,$event_ID); // list object with msg
            }
        }
    }
    /*
     * when calling from prm-events be sure to set event_ID
     *
     */
    public static function edit($page,$object,$ID,$event_ID=NULL){
        include_once(PRM_PUGIN_PATH.'classes/prm-options.php');
        if($object != 'series'){ // don't remove trailing 's' for series
            $object_sigular = rtrim($object,'s'); //table in singular
        }else{ $object_sigular = $object; }
        $set_event_ID = (!empty($event_ID))? 'e='.$event_ID.'&' : '';
        //set return if coming from results list
        $return = ($_GET['return'] == '3')? '&return=3' : '';
        echo '<form action="?page='. $page .'&m=m&o='. $object .'&a=update&i='. $ID .'&'. $set_event_ID .'f=me'. $return .'" method="post">';
        global $wpdb;
        if($object == 'racers'){ // we need to join tye racer and competitor table row so all fields can be reviewed
            $sql = 'select r.*,c.* from '.$wpdb->prefix.'prm_racers r left join '. $wpdb->prefix. 'prm_competitors c
                    on r.competitors_ID = c.competitors_ID
                    where r.ID = '.$ID;

        }elseif($object == 'competitors'){ // fix when renaming ID to object_ID
            $sql = 'select * from '.$wpdb->prefix.'prm_'. $object .' where competitors_ID = '.$ID;
        }else $sql = 'select * from '.$wpdb->prefix.'prm_'. $object .' where ID = '.$ID;
        $row = $wpdb->get_row($sql);
        echo '<h2>Edit '. ucfirst($object_sigular) .'</h2>';
        foreach($row as $key => $value){
            //test for special fields that need attention
            if(strpos($key,'_type')) $special = 'type';
                // Pro fields
            elseif($key == 'offers_ID') $special = 'Pro';
            elseif($key == 'teams_ID') $special = 'Pro';
            elseif($key == 'max_team_size') $special = 'Pro';

            // don't allow change of competitor itself when editing racer
            elseif($key == 'competitors_ID' && $object == 'racers') $special = 'ignore';
            elseif($key == 'events_ID' && $object == 'racers') $special = 'ignore';

            elseif(strpos($key,'_date')) $special = 'date';
            elseif(strpos($key,'_time')) $special = 'time';
            elseif(strpos($key,'_sys')) $special = 'system';
            elseif(strpos($key,'_ID')) $special = 'list_ID';
            else $special = $key;
            //if($key == 'competitors_ID') die(var_dump($special));
            switch($special){
                case 'ID':
                case 'Pro':
                case 'ignore':

                    //ignore
                break;
                case 'system': // don't add, internal system field
                    break;
                case 'list_ID': // need to build options from relevant table

                    $target_object = rtrim($key,'_ID'); // strip _ID from field to get target object name
                    // we need to set ID to be set as selected where possible
                    $target_object_id = NULL;
                    //if($target_object == 'events') $target_object_id = $event_ID;
                    $target_object_id = $value;
                    $display_key = ucfirst(str_replace('_',' ',$target_object)); //replace '-' with space and capitalise
                    $display_key = rtrim($display_key,'s'); // change to sigular since most keys are plural
                    $options = '<p><label for="'. $key .'">'. $display_key .': <select name="'. $key .'">';
                    $options .= prm_Options::table_options($target_object,$target_object_id);
                    $options .= '</select></label></p>';
                    echo $options;
                break;
                case 'date':
                    $display_key = ucfirst(str_replace('_',' ',$key)); //replace '-' with space and capitalise
                    $display_key .= ' (yyyy-mm-dd)';
                    echo '<p><label for="'. $key .'">'. $display_key .': <input type="text" name="'. $key .'" value="'. $value .'" ></label></p>';
                break;
                case 'message': //text areas
                case 'competitor_notes':
                case 'racers_notes':
                case 'notes':
                case 'description':
                    $display_key = ucfirst(str_replace('_',' ',$key)); //replace '-' with space and capitalise
                    $text = '<p><label for="'. $key .'">'. $display_key .': <br><textarea cols="60" type="text" name="'. $key .'">'. $value.'</textarea></label></p>';
                    echo $text;
                break;
                case 'state':
                case 'gender':
                case 'type': //need to generate select list
                    $display_key = $key;
                    if($special == 'type')$display_key = substr($key,0,strpos($key, '_type'));
                    $display_key = ucfirst(str_replace('_',' ',$display_key)); //replace '-' with space and capitalise
                    $options = '<p><label for="'. $key .'">'. $display_key .': <select name="'. $key .'">';
                    $options .= prm_Options::options('select',$key,$value);
                    $options .= '</select></label></p>';
                    echo $options;
                break;
                default:
                    $display_key = ucfirst($key); //make 1st letter upp case
                    $display_key = str_replace('_',' ',$display_key); //replace '-' with space
                    echo '<p><label for="'. $key .'">'. $display_key .': <input type="text" name="'. $key .'" value="'. $value .'" ></label></p>';
            }
        }
        echo '<p><input type="image" src="'. PRM_PLUGIN_URL .'images/save.jpg" alt="Update '. $object_sigular .'"></p>';
        switch($object){
            case 'competitors':
            case 'events':
            case 'courses':
            case 'settings':
            case 'crew':
            case 'series':
                //only include events ID if object is child of events
                break;
            default:
                echo '<input name="events_ID" type="hidden" value="'. $event_ID .'">';
                break;
        }
        echo '</form>';
    }
    /*
     * we're catching most error in code however if we get here out a message
     */
    public static function error_handler($error_code,$object){ // set mesg as per error
        switch($error_code){
            case 893: // saw this online, duplicate key?
                $msg = 12;
                break;
            case 1062: // duplicate key
                if($object == 'racers') $msg = 6;
                else $msg = 12;
                break;
            case 0; // no change to record
                $msg = 16;
                break;
            case 1048:
                if($object == 'courses') $msg = 21;
                break;
            default:
                if(PRM_DEBUG == 'Yes') die($error_code.' has not got a msg assigned in model->error_handler');
                $msg = 11;
        }
        return $msg;
    }
    /*
     * columns needs to be an array with the DB fields to insert
     * typically $_POST should be passed
     */
    public static function insert($page,$object,$columns,$event_ID=NULL){
        global $wpdb;
        $msg = NULL; //set if an exception occurs
        //Validations before insert

            // competitor - don't allow second with same email

            // racer - don't add same competitor into same race again
        $sql  = "insert into ".$wpdb->prefix.'prm_'.$object;
        $fields = ' (';
        $values = 'values(';
        foreach($columns as $key =>$value){
            switch($key){ // take acton if required for each field
                case 'x': //part of $_POST, ignore
                case 'y': //part of $_POST, ignore
                    break;
                case 'bib_number':
                    if(!empty($value)){ // if empty set to NULL
                        if($object == 'racers'){ // we need to check if the bib is not in use
                            include_once(PRM_PUGIN_PATH.'classes/prm-bibs.php');
                            $unique = prm_Bibs::unique($value,$event_ID);
                            if(is_null($unique)){ // all is good -> add
                                $fields .= $key.',';
                                $values .= '"'.$value.'",';
                            }else{ // already in use
                                $fields .= $key.',';
                                $values .= 'NULL,';
                                $msg = 6; // display msg that bib is already in use
                            }
                        }
                    }else{//field is empty
                        $fields .= $key.',';
                        $values .= 'NULL,';
                    }
                    break;
                case 'email': //if no email entered set to NULL
                    if(empty($value)){
                        $fields .= $key.',';
                        $values .= 'NULL,';
                    }
                    break;
                default:
                    if(empty($value)){
                        $fields .= $key.',';
                        $values .= 'NULL,';
                    }else{
                        $fields .= $key.',';
                        $values .= '"'.$value.'",';
                   }
            }
        }
        $fields = rtrim($fields,','); //remove trailing comma
        $values = rtrim($values,',');
        $fields .=')';
        $sql .= $fields.$values.')';
        $result = $wpdb->query($sql);
        if (!$result) { // insert failed
            $error_code = mysql_errno();
            $msg = self::error_handler($error_code,$object);
            }
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
        // after adding a new option the came_from isn't working, so short term fix
        if($object == 'options'){
            $object_fix = ltrim($_REQUEST['page'],'prm-');
            prm_View::object_list($object_fix);
            exit;
        }
        switch($object){ // take action if required based on object
            case 'racers': //need to races_ID and display that race
                include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
                $racer = new prm_Racer($wpdb->insert_id);
                $races_ID = $racer->get_races_ID();
                prm_Racer::list_items($races_ID,'races','entries',NULL,$wpdb->insert_id,$msg);
                break;
            default:
                prm_View::object_list($object,$wpdb->insert_id,$msg,$event_ID);
        }
    }
    /*
     * fields needs to be an array of DB coloumns name => value,
     * typically the is $_POST
     * event_ID should be passed for events transactions
     */
    public static function update($page,$object,$ID,$fields,$event_ID=NULL){
        global $wpdb;
        $msg = NULL;
        //$last_key = end(array_keys($fields));
        if($object == 'racers'){ // we need to join tye racer and competitor table row so all fields can be reviewed
            $sql = 'update '.$wpdb->prefix.'prm_racers r left join '. $wpdb->prefix. 'prm_competitors c
                    on r.competitors_ID = c.competitors_ID set ';

        }else
        $sql = 'update '.$wpdb->prefix.'prm_'.$object .' set ';
        foreach($fields as $key => $value){
            if(strpos($key,'date')){ //set some special cases that we need to deal with before update
                $special = 'date';
            }elseif(strpos($key,'time')){ // write NULL if field is empty
                $special = 'date';
            }else $special = $key;

            switch($special){
                case 'x':
                case 'y':
                case 'ID':
                    //don't add to update
                break;
                case 'bib_number': // don't allow bib# 0
                    if($value == 0){
                        $sql .= $key.' = NULL,';
                    }else $sql .= $key.' = "'.$value.'",';
                    break;
                case 'date': //if date is NULL ensure we write NULL back again
                    if(empty($value)) $sql .= $key.' = NULL,';
                    else {
                        if($value == '00:00:00') $sql .= $key.' = NULL,';
                        else $sql .= $key.' = "'.$value.'",';
                    }
                    break;

                default:
                    $sql .= $key.' = "'.$value.'",';
            }
        }
        $sql = rtrim($sql,','); //remove last comma
        if($object == 'racers') $sql .= ' where r.ID = '.$ID;
        elseif($object == 'competitors'){ // fix when rebaming ID to object_ID
            $sql .= ' where competitors_ID = '.$ID;
        }else $sql .= ' where ID = '.$ID;
        $result = $wpdb->query($sql);
        if($result){
            $msg = 7; // successful update
        }
        else { // update failed
            //if($result == 0) $msg = 16; // no change was made
                $error_code = mysql_errno();
                $msg = self::error_handler($error_code,$object);

            }

        // call relevant list and shpw msg at top
        if($object == 'racers'){ // need to make list call via racers
            if($_GET['return'] == '3'){ // we need to return to results list
                include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
                $racer = new prm_Racer($ID);
                $races_ID = $racer->get_races_ID();
                include_once(PRM_PUGIN_PATH.'classes/prm-list-results.php');
                $results = new prm_Results_List($races_ID);
                $results->prepare_items();
                $results->display();
            }else{
                include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
                // at this stage I don't know where we came from so I'm returning to entries of the race this racer belongs to
                $where = 'races';
                $type = 'entries';

                $racer = new prm_Racer($ID);
                $races_ID = $racer->get_races_ID();
                if($racer->status >= 1 && is_null($racer->get_bib())){
                    if($msg != 6) $msg = 18; //only change if current msg isn't duplicate bib
                }
                prm_Racer::list_items($races_ID,$where,$type,$event_ID,$ID,$msg);
            }
        }elseif($object == 'races'){ //check that events_ID is set
            if(is_null($event_ID)){
                include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
                $event_ID = prm_Races::get_var('events_ID',$ID);
                prm_View::object_list($object,$ID,$msg,$event_ID);
            }
        }elseif($object == 'settings'){
            include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
            prm_View::view($page,'settings',PRM_ORG_ID,$msg);
        }else {
            include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
            prm_View::object_list($object,$ID,$msg,$event_ID);
        }
    }

}