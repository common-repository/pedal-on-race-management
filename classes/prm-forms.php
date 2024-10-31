<?php
/**
 *
 */
class prm_Forms{
    /*
     * display fields of table $table
     * $fields = array('label' =>'field_name')
     * use prefix s_ for select list
     * t_ = textarea
     */
    public static function add($table,$fields,$event_ID){
        $t = substr($table,4); //get table without prm_
        $output = '<form action="?page=prm-events&t='. $t .'&action=insert&event='. $event_ID .'" method="post">';
        foreach($fields as $key => $value){ //$key = field, $value = label
            $type = substr($key,0,2); //get special type if set x_
            switch($type){
                case 's_': // build select
                    $option = substr($key,2); //strip the prefix s_
                    $output .= '<p><label for="'. $option .'">'. $value .'<select name="'. $option .'" value="">';
                    include_once(PRM_PUGIN_PATH.'classes/prm-options.php');
                    $output .= prm_Options::options('select',$option);
                    $output .= '</select></label></p>';
                break;
                case 't_': // textarea
                    $option_key = substr($key,2); //strip the prefix t_
                    $output .= '<label for="'. $option_key .'">'. $value .'<br><textarea cols="60" type="text" name="'. $option_key .'"></textarea></label></p>';
                break;
                default: //type text
                    $output .= '<p><label for="'.$key.'">'.$value.'<input type="text" name = "'. $key .'" value = ""></label></p>';

            }
        }// eof foreach
        $output .= '<p><input type="image" src="'. PRM_PLUGIN_URL .'images/save.jpg" alt="Add Competitor" ></p>';
        $output .='</form>';
        include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
        $output .= prm_Help::display_msg('all','add');
        return $output;
    }
    public static function bread_crumb($event_ID,$t){

        include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
        $event = new prm_Events($event_ID);
        $name = $event->get_name();
        $bread_crumb = '<a href="'.PRM_ADMIN_URL.'prm-events">Events</a> > <a href="'.PRM_ADMIN_URL.'prm-events&action=view&event='.$event_ID.'" >'.$name.'</a>';

        return $bread_crumb;

    }
    public static function edit($table,$ID,$event_ID){
        include_once(PRM_PUGIN_PATH.'classes/prm-options.php');
        $t = substr($table,4); //get table without prm_
        $ts = rtrim($t,'s'); //table in singular
        echo '<form action="?page=prm-events&t='. $t .'&action=update&'. $ts .'='. $ID .'" method="post">';
        global $wpdb;
        $sql = 'select * from '.$wpdb->prefix.$table.' where ID = '.$ID;
        $row = $wpdb->get_row($sql);
        echo '<h2>Edit '. ucfirst($ts) .'</h2>';
        foreach($row as $key => $value){
            //test for special fields that need attention
            if(strpos($key,'_type')) $special = 'type';
            elseif(strpos($key,'_date')) $special = 'date';
            elseif(strpos($key,'_time')) $special = 'time';
            else $special = $key;
            switch($special){
                case 'ID':
                case 'offers_ID':
                case 'events_ID':
                case 'courses_ID':
                case 'competitors_ID':
                    //do nothing - can't allow eding of keys
                break;
                case 'date':
                    $display_key = ucfirst(str_replace('_',' ',$key)); //replace '-' with space and capitalise
                    $display_key .= ' (yyyy-mm-dd)';
                    echo '<p><label for="'. $key .'">'. $display_key .': <input type="text" name="'. $key .'" value="'. $value .'" ></label></p>';
                break;
                case 'message': //text areas
                case 'notes':
                case 'description':
                    $display_key = ucfirst(str_replace('_',' ',$key)); //replace '-' with space and capitalise
                    $text = '<p><label for="'. $key .'">'. $display_key .': <br><textarea cols="60" type="text" name="'. $key .'">'. $value.'</textarea></label></p>';
                    echo $text;
                break;
                case 'state':
                case 'gender':
                case 'type': //need to generate select list
                    $display_key = ucfirst(str_replace('_',' ',$key)); //replace '-' with space and capitalise
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
        echo '<p><input type="image" src="'. PRM_PLUGIN_URL .'images/save.jpg" alt="Update '. $ts .'"></p>';
        echo '<input type="hidden" value="'. $event_ID .'">';
        echo '</form>';
    }
    /*
     * method needs an array with the DB fields to insert
     * if no array is passed POST will be used
     */
    public static function insert($table,$columns=0,$event_ID){
        global $wpdb;
        if($columns == 0){//
            $columns = array();
            $columns .= (is_array($_POST)) ? $_POST : NULL;
        }
        $sql  = "insert into ".$wpdb->prefix.$table;
        $fields = ' (';
        $values = 'values(';
        foreach($columns as $key =>$value){
            if($key != 'x' && $key != 'y'){
                $fields .= $key.',';
                $values .= '"'.$value.'",';
            }
        }
        $fields = rtrim($fields,','); //remove trailing comma
        $values = rtrim($values,',');
        $fields .=')';
        $sql .= $fields.$values.')';
        $result = $wpdb->query($sql);
        self::view($table,$wpdb->insert_id,$event_ID);
    }
    public static function update($table,$ID){
        global $wpdb;
        $event_ID = $_POST['event_ID'];
        //$last_key = end(array_keys($fields));
        $sql = 'update '.$wpdb->prefix.$table.' set ';
        foreach($_POST as $key => $value){
            switch($key){
                case 'x':
                case 'y':
                    //don't add to update
                break;
                case 'bib': //we need to check that the bib# is unique
                    $msg = NULL; //change to ID 6 if bib is in use
                    include_once(PRM_PUGIN_PATH.'classes/prm-bibs.php');
                    $bib_exists = prm_Bibs::unique($value,$event_ID);
                    if(is_null($bib_exists)){ /* all is good update bib*/
                        $sql .= $key.' = "'.$value.'",';
                    }else{ //bib is already in use
                        $msg = 6; // msg that bib is in use
                    }
                break;
                default:
                    $sql .= $key.' = "'.$value.'",';
            }
        }
        $sql = rtrim($sql,','); //remove last comma
        $sql .= ' where ID = '.$ID;
        $result = $wpdb->query($sql);
        self::view($table,$ID,$event_ID, $msg);
    }
    /*
     * to display msg hand-in prm_help->ID
     * if msg != NULL display message by ID
     */
    public static function view($table,$ID,$event_ID,$msg=NULL){
        global $wpdb;
        $t = substr($table,4); //get table without prm_
        $ts = rtrim($t,'s'); //table in singular
        $sql = 'select * from '.$wpdb->prefix.$table.' where ID = '.$ID;
        $row = $wpdb->get_row($sql);
        echo self::bread_crumb($event_ID,$t);
        echo '<h2>'. ucfirst($ts) .' Details</h2>'; // screen name
        // display msg if set
        if(!is_null($msg)){ // we have a msg to display
            include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
            echo prm_Help::get_msg($msg);
        }
        echo '<table>';
        foreach($row as $key => $value){
            //clean $key for display
            $display_key = ucfirst($key);
            $display_key = str_replace('_',' ',$display_key);
            switch($key){
                case 'competitors_ID':
                    include_once(PRM_PUGIN_PATH.'classes/prm-competitors.php');
                    $competitor = new prm_Competitor($value);
                    $name = $competitor->get_name();
                    $display_key = rtrim($display_key,'s ID'); //remove ID from the end
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $name.' (id:'.$value .')</td></tr>';
                break;
                case 'events_ID':
                    include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
                    $event = new prm_Events($value);
                    $name = $event->get_name();
                    $display_key = rtrim($display_key,'s ID'); //remove ID from the end
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $name.' (id:'.$value .')</td></tr>';
                break;
                case 'courses_ID':
                    include_once(PRM_PUGIN_PATH.'classes/prm-courses.php');
                    $course = new prm_Courses($value);
                    $name = $course->get_name();
                    $display_key = rtrim($display_key,'s ID'); //remove ID from the end
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $name.' (id:'.$value .')</td></tr>';
                break;
                case 'offers_ID':
                    // do nothing, neede for Pro
                    break;
                default:
                    echo '<tr><td width="25%"><strong>'. $display_key .':</strong></td><td>'. $value .'</td></tr>';
            }
        }
        echo '</table>';
        $t = substr($table,4); //get table without prm_
        $ts = rtrim($t,'s'); //table as sigular
        echo '<a href="'. PRM_ADMIN_URL .'prm-events&t='. $t .'&action=edit&'. $ts .'='. $ID .'"><img src="'. PRM_PLUGIN_URL .'images/edit.jpg" /></a>';
        if($t == 'racers'){ //need to add return to Adding racers to race course
                    echo '<a href="'.PRM_ADMIN_URL.'prm-events&list=racers&action=view&event='.$event_ID.'">Return to: Add Racers</a>';
                }
    }
}