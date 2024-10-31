<?php
/**
 *
 */
class prm_Racer{
    var $racer_ID;
    var $racer; // properties (table columns)
    var $status;

    function __construct($racer_ID){
        global $wpdb;
        $this->racer_ID = $racer_ID;
        $this->table_name_r = $wpdb->prefix.'prm_racers';
        $this->table_name_c = $wpdb->prefix.'prm_competitors';
        $sql = 'select r.*, c.first_name, c.last_name from '. $this->table_name_r .' r, '.
                $this->table_name_c. ' c where r.ID = '.$this->racer_ID.' and r.competitors_ID =
                c.competitors_ID';
        $this->racer = $wpdb->get_row($sql);
        $this->status = $this->racer->status;
    }


    public function bread_crumb(){
        $bread_crumb = '<a href="'.PRM_ADMIN_URL.'prm-events">Events</a> > <a href="'.PRM_ADMIN_URL.'prm-events&action=view&event='.$this->event->ID.'" >'.$this->event->name.'</a>';
        return $bread_crumb;
    }
    /*
     * return race time if status = 7, normal finish
     * if status 6 (finished with exception) return TBA
     */
    public function calc_result(){
        if($this->status == 6) return 'TBA';
        if($this->status == 7){// only if racer has finished normally
            //get start time from race
            include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $race = new prm_Races($this->get_races_ID());
            $race_start = new DateTime($race->race->started_sys);
            $finished = new DateTime($this->racer->finish_sys);
            // when using php time there is an hour diff to mysql time
            $elapsed = $race_start->diff($finished);
            return $elapsed->format( '%H:%I:%S' );
        }else {return false;}
    }
    /*
     * check if this is the last finisher of this race and if so set race status to 7 (all finished)
     */
    public function FIN(){
        //need to set status = 7 unless current status is 4 (exception)
        // set finish time to now()
        // set result type = FIN
        global $wpdb;
        $new_status = ($this->status() == 4)? 6 : 7;
        $this->status = $new_status;
        $sql = 'update '.$wpdb->prefix.'prm_racers set
                status = '.$this->status.',
                finish_sys = NOW(),
                result_type = "FIN"
                where ID = '.$this->racer_ID;
        $result = $wpdb->query($sql);
        // check if any one is left on-course

        self::list_items($this->get_races_ID(),'races','on-course',$this->racer_ID);
    }
    public function DNF(){
            //need to set status = 9 since no result will be processed for them
            // don't set finish time
            // set result type = DNF
            global $wpdb;

            $this->status = 9;
            $sql = 'update '.$wpdb->prefix.'prm_racers set
                    status = 9,
                    result_type = "DNF"
                    where ID = '.$this->racer_ID;
            $result = $wpdb->query($sql);
            self::list_items($this->get_races_ID(),'races','on-course',$this->racer_ID);
        }
    public function DQ(){
        //need to set status = 9 since no result will be processed for them
        // don't set finish time
        // set result type = DQ
        global $wpdb;

        $this->status = 9;
        $sql = 'update '.$wpdb->prefix.'prm_racers set
                status = 9,
                result_type = "DQ"
                where ID = '.$this->racer_ID;
        $result = $wpdb->query($sql);
        self::list_items($this->get_races_ID(),'races','on-course',$this->racer_ID);
    }
    public function get_bib(){
        //$bib = (empty($this->racer->bib))? NULL : $this->racer->bib;
        return $this->racer->bib_number;
    }
    public function get_races_ID(){
        return $this->racer->races_ID;
    }
    public function get_events_ID(){
        return $this->racer->events_ID;
    }
    public function get_race_time(){
        return $this->racer->race_time;
    }
    public static function lights($live=NULL){ //return a list of all lights and their meaning
        $path = PRM_PLUGIN_URL.'images/';
        // 0 default, entered
        // 1 registered
        $lights = ' <img src="'.$path.'orange.jpg" width="12" /> Signed On  '; // 3
        if(is_null($live)) $lights .= ' <img src="'.$path.'green-x.jpg" width="12" /> Racing with Exception  '; // 4
        $lights .= ' <img src="'.$path.'green.jpg" width="12" /> Racing  ';  // 5
        if(is_null($live)) $lights .= ' <img src="'.$path.'red-x.jpg" width="12" /> Finished with Exception  '; // 6
        $lights .= ' <img src="'.$path.'red.jpg" width="12" /> Finished  ';  // 7
        if(is_null($live)) $lights .= '<img src="'.$path.'grey.jpg" width="12" /> Result published'; // 9
        return $lights;
    }
    /*
     * ID = races_ID, needs to be set if where = races
     * where = list racers by races or events
     * type = reason to list, eg entries, on-course, result
     * update_ID = set if returning from an update to show row in green
     */
    public static function list_items($ID,$where,$type,$events_ID=NULL,$updated_ID=NULL,$msg=NULL){
        if($where == 'races'){
            if(PRM_DEBUG == 'Yes'){
                if(empty($ID)) die('races_ID must be set when using type = races in racers->list');
            }
            $races_ID = $ID;
        }
        //show msg if set
        if(!is_null($msg)){ //display message first if applicable
            include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
            echo prm_Help::get_msg($msg);
        }
        include_once(PRM_PUGIN_PATH. 'classes/prm-list-racers.php');
        $events_ID = ($races_ID > 0)? NULL : $events_ID;
        $list = new prm_Racers_List($races_ID,$where,$type,$events_ID,$updated_ID);
        $list->prepare_items();
        $list->display();
    }
    public function number($of_table){
        global $wpdb;
        $sql = 'select count(*) as number_of from '.$wpdb->prefix.'prm_'.$of_table.' where races_ID = '.$this->race_ID;
        $row = $wpdb->get_row($sql);
        // return number of or 0 if none or false if query fails
        return ($row->number_of > 0) ? $row->number_of :  0;
    }
    public static function on_course($races_ID){ //check if any racers are still on course and return how amny are or 0 if none
        global $wpdb;
        $sql = 'select count(*) as racers from '.$wpdb->prefix.'prm_racers
                where status between 4 and 5 and races_ID = '.$races_ID;
        $row = $wpdb->get_row($sql);
        $racers = $row->racers;
        return $racers;
    }
    public function rego(){
        //need to set status = 1
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_racers set status = 1 where ID = '.$this->racer_ID;
        $result = $wpdb->query($sql);
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
        prm_View::view($_REQUEST['page'],'racers',$this->racer_ID);
        //prm_View::object_list('racers',$this->racer_ID,NULL,$this->get_races_ID() ,'race');
    }
    /*
     * returns the sum of children eg courses of an event
     * of any table that contains events_ID eg
     * races, courses, entries, legs, crew
     * provide without prefixes
    */
    public static function race_on($races_id){ // race has started, change status to 5 of all signed-in racers
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_racers set status = 5 where races_ID = '.$races_id.' and status = 3';
        $result = $wpdb->query($sql);
        return $result;
    }
    public static function result_lights(){ //return a list of result lights and their meaning
        //$path = PRM_PLUGIN_URL.'images/';
        //$lights = ' <img src="'.$path.'orange.jpg" width="12" /> Exception  '; // status 6
        //$lights .= ' <img src="'.$path.'green.jpg" width="12" /> Finished  ';  // 5
        //$lights .= ' <img src="'.$path.'red.jpg" width="12" /> DNF or DQ  ';  // 7
        $lights = '<strong>Exception</strong> = an exception was raised during the race<BR>';
        $lights .= '<strong>FIN</strong> = regular FINish';
        $lights .= '  <strong>DNF</strong> = Did Not Finish the race';
        $lights .= '  <strong>DQ</strong> = Was disqualified from the race';
        return $lights;
    }
    /*public function result_status($size=30){ //return relevant status light, size can be set
        $path = PRM_PLUGIN_URL.'images/';
        //set my special flag
        if($this->status == 6) $special = 'ORG';
        elseif($this->racer->result_type == 'FIN') $special = 'GRN';
        elseif($this->racer->result_type == 'DNF') $special = 'RED';
        elseif($this->racer->result_type == 'DQ') $special = 'RED';
        switch ($special){
            case 'ORG':
                //$light = '<img src="'.$path.'orange.jpg" width="'.$size.'" />';
                $light = '<span style="color: orange;">Exception</span>';
                break;
            case 'GRN':
                //$light = '<img src="'.$path.'green.jpg" width="'.$size.'" />';
                $light = '<span style="color: green;">FIN</span>';
                break;
            case 'RED':
                //$light = '<img src="'.$path.'red.jpg" width="'.$size.'" />';
                if($this->racer->result_type == 'DNF') $light = '<span style="color: red;">DNF</span>';
                elseif($this->racer->result_type == 'DQ') $light = '<span style="color: red;">DQ</span>';
                break;
        }
        return $light;
    }*/
    public function sign_in(){ // competitor signs in to race
        //need to set status = 3, unless the race has already started in which case he needs to enter the race
        global $wpdb;
        //just a quick check to make sure racer has a bib#

        if(is_null($this->get_bib())){
            $this->list_items($this->get_races_ID(),'races','entries',NULL,$this->racer_ID,19);
        }else{
            //find out if the race has started
            include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $race = new prm_Races($this->get_races_ID());
            $race_status = $race->get_status();
            $new_status = ($race_status == 5)? $race_status : 3;
            $this->status = $new_status;
            $sql = 'update '.$wpdb->prefix.'prm_racers set status = '. $new_status .' where ID = '.$this->racer_ID;
            $result = $wpdb->query($sql);
            //include_once(PRM_PUGIN_PATH.'classes/prm-views.php');

            $this->list_items($this->get_races_ID(),'races','entries',NULL,$this->racer_ID);
        }
    }
    public function sum($table,$column){ //get sum of a column
        global $wpdb;
        $sql = 'select sum('.$column.') as sum_of from '.$wpdb->prefix.'prm_'.$table.' where races_ID = '.$this->race_ID;
        $row = $wpdb->get_row($sql);
        // return number of or 0 if none or false if query fails
        return ($row->sum_of > 0) ? $row->sum_of :  0;
    }
    public function status($size=30){ //return relevant status light, size can be set
        $path = PRM_PLUGIN_URL.'images/';
        switch ($this->status){
            case 0:
                $light = 'not registered';
                break;
            case 1:
                $light = 'is registered';
                break;
            case 3: // signed in
                $light = '<img src="'.$path.'orange.jpg" width="'.$size.'" />';
                break;
            case 4: // racing with exception
                $light = '<img src="'.$path.'green-x.jpg" width="'.$size.'" />';
                break;
            case 5: // racing
                $light = '<img src="'.$path.'green.jpg" width="'.$size.'" />';
                break;
            case 6: // finished with exception
                $light = '<img src="'.$path.'red-x.jpg" width="'.$size.'" />';
                break;
            case 7: // finished
                $light = '<img src="'.$path.'red.jpg" width="'.$size.'" />';
                break;
            case 8:
            case 9: // results published
                $light = '<img src="'.$path.'grey.jpg" width="'.$size.'" />';
                break;
        }
        return $light;
    }
    public function update_result($race_time){
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_racers set race_time = "'. $race_time->format( '%H:%I:%S' ) .'"
                where ID = '.$this->racer_ID;
        $result = $wpdb->query($sql);
        return $result;
    }
    public function update_status($status){
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_racers set status = "'. $status .'"
                where ID = '.$this->racer_ID;
        $result = $wpdb->query($sql);
        $this->status = $status;
        return $result;
    }
    /*
     * for = course or race
     * place - the ranking achieved
     */
    public function update_place($for, $place){
        global $wpdb;
        $set = ($for == 'course')? 'set course_placing = '.$place : 'set race_placing = '.$place;
        $sql = 'update '.$wpdb->prefix.'prm_racers '. $set .'
                where ID = '.$this->racer_ID;
        $result = $wpdb->query($sql);
        return $result;
    }
}
?>