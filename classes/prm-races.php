<?php
/**
 * This control the races and builds the race central screen
 */
class prm_Races{
    var $races_ID;
    var $event_ID;
    var $race; // holds all properties of this race
    var $status;
    function __construct($races_ID){
        $this->races_ID = $races_ID;
        global $wpdb;

        $sql = 'select * from '.$wpdb->prefix.'prm_races where ID = '.$this->races_ID;
        $this->race = $wpdb->get_row($sql);
        $this->event_ID = $this->race->events_ID;
        $this->status = $this->race->status;
        // check status
        $this->check_status();
    }
    /*
     * if status > 3 -> no need to check since race is in progress (5) or closed (7)
     * On start of race status is changed to 5
     * we need to determine what the status is, check object and update if different
     */
    private function check_status(){
        if($this->status == 5){ // check if all racers have finisehed and if so close race
            $this->close_race();
        }
        if($this->status < 5){ // if 5 or greater no change required
            $status = ($this->race_sigins() > 0)? 3 : 0;
            if($status != $this->status){ //update status
                $this->status = $status;
                $this->update_status($status);
            }
        }

    }
    /*
     * if manual = 1:
     *   used to close race manually
     *   check if all racers have finished and if so close race
     *   else return msg
     * if manual = NULL
     *   check if on-course = 0 if so close race
     */
    public function close_race($manual=NULL){
        // check if all racers have finished
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        $racers = prm_Racer::on_course($this->races_ID); // racers will contain on-course racers
        if($racers == 0){ // no racers on-course
            $this->update_status(7);
        }
        if($manual){ // return a message
            $msg = ($this->status == 7)? 7 : 17;
            include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
            prm_View::object_list('races',$this->races_ID,$msg);
        }
    }
    public static  function get_entries($race_ID){ // return number entries for this race
            global $wpdb;
            $sql = 'select count(*) as number_of from '.$wpdb->prefix.'prm_racers where races_ID = '.$race_ID;
            $row = $wpdb->get_row($sql);
            // return number of or 0 if none or false if query fails
            return ($row->number_of > 0) ? $row->number_of :  0;
        }
    public static function lights(){ //return a list of all lights and their meaning
        $path = PRM_PLUGIN_URL.'images/';

        $lights  = '<img src="'.$path.'orange.jpg" width="12" />  Sign-in in progress  ';
        $lights .= ' <img src="'.$path.'green.jpg" width="12" /> Race in progress  ';
        $lights .= ' <img src="'.$path.'red.jpg" width="12" /> Race finished ';
        $lights .= ' <img src="'.$path.'grey.jpg" width="12" /> Results published';

        return $lights;
    }
    public function list_all(){ // list all races
        include_once(PRM_PUGIN_PATH.'/classes/prm-list-races.php');
        $races = new prm_Races_List(NULL,$this->event_ID);
        $races->prepare_items();
        $races->display();
    }

    public static function get_id($events_ID){ // get an races_ID
        global $wpdb;
        $sql = 'select ID from '.$wpdb->prefix.'prm_races where events_ID = '.$events_ID;
        $row = $wpdb->get_row($sql);
        if($row->ID > 0) return $row->ID;
        else return NULL;
    }
    public function get_status(){
        return $this->status;
    }
    public static function get_var($name,$races_ID){
        global $wpdb;
        $sql = 'select '. $name .' as name from '.$wpdb->prefix.'prm_races where ID = '.$races_ID;
        $row = $wpdb->get_row($sql);
        return $row->name;
    }
    public function placings(){
        global $wpdb;
        $sql = 'select * from '.$wpdb->prefix.'prm_racers
                where races_ID = '.$this->races_ID.' and status between 6 and 7
                order by race_time ASC';
        $rows = $wpdb->get_results($sql);
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        $place = 0;
        foreach($rows as $row){
            $place++;
            $racer = new prm_Racer($row->ID);
            $racer->update_place('race',$place);
            unset($racer);
        }
        $this->update_result_status();
        include_once(PRM_PUGIN_PATH.'classes/prm-list-results.php');
        $results = new prm_Results_List($this->races_ID);
        $results->prepare_items();
        $results->display();
    }
    /*
     * publish results from current list, that is all racers in status 6/7
     * -> update all to status 8 (9 is used once they've been emailed
     */
    public function publish(){
        global $wpdb;
        $sql = 'select * from '.$wpdb->prefix.'prm_racers
                where races_ID = '.$this->races_ID.' and status between 6 and 7';
        $rows = $wpdb->get_results($sql);
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        foreach($rows as $row){
            $racer = new prm_Racer($row->ID);
            $result = $racer->update_status(8);
            unset($racer);
        }
        //update race status to 8
        $this->update_status(8);
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');

        prm_View::object_list('races',$this->races_ID,20,$this->event_ID);
    }
    /*
     * if race in progress return time elapsed
     * else return statement as per status
     */
    public function race_clock(){
        if($this->status == 5){// only if race is in progress
            $race_start = new DateTime($this->race->started_sys);
            // when using php time there is an hour diff to mysql time
            global $wpdb;
            $sql = 'select now() as my_time';
            $result = $wpdb->get_row($sql);
            $my_time = $result->my_time;
            $my_time = new DateTime($my_time);
            $elapsed = $race_start->diff($my_time);
            return $elapsed->format( '%H:%I:%S' );
        }else {
            switch($this->status){
                case 0:
                case 3:
                    return 'not started';
                    break;
                case 7:
                case 9:
                    return 'finished';
                    break;
            }
        }
    }
    public function race_sigins(){ // check if we have any signins as yet
        global $wpdb;
        $sql = 'select count(*) as signins from '.$wpdb->prefix.'prm_racers where status = 3 and races_ID = '.$this->races_ID;
        $result = $wpdb->get_row($sql);
        return $result->signins;
    }
    /*
     * calc the result of each racer and save to DB
     * list all results with lights
     */
    public function results(){
        global $wpdb;
        $sql = 'select * from '.$wpdb->prefix.'prm_racers where races_ID = '.$this->races_ID;
        $rows = $wpdb->get_results($sql);
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        foreach($rows as $row){
            $racer = new prm_Racer($row->ID);
            $finish_time = new DateTime($racer->racer->finish_sys);
            $start_time = new DateTime($this->race->started_sys);
            $race_time = $start_time->diff($finish_time);
            $result = $racer->update_result($race_time);
            unset($racer);
        }
        include_once(PRM_PUGIN_PATH.'classes/prm-list-results.php');
        $results = new prm_Results_List($this->races_ID);
        $results->prepare_items();
        $results->display();
    }
    public function start(){//start of race
        global $wpdb;
        // update DB
        $sql = 'update '.$wpdb->prefix.'prm_races set started_sys = NOW() where ID = '.$this->races_ID;
        $result = $wpdb->query($sql);
        //update status
        $this->update_status(5);
        $this->status = 5;
        //update status of all racers to 5
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        prm_Racer::race_on($this->races_ID);
        // list races
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
        prm_View::object_list('races',$this->races_ID,7,$this->event_ID);
    }
    public function status($size=30){ //return relevant status light, size can be set
        $path = PRM_PLUGIN_URL.'images/';
        switch ($this->status){
            case 0: // no light set, waiting for sign-ins
                $light = 'no sign-ins';
                break;
            case 3: // sign in in progress
                $light = '<img src="'.$path.'orange.jpg" width="'.$size.'" />';
                break;
            case 5: // race in progress
                $light = '<img src="'.$path.'green.jpg" width="'.$size.'" />';
                break;
            case 7: // race finished
                $light = '<img src="'.$path.'red.jpg" width="'.$size.'" />';
                break;
            case 8: // results published
                $light = '<img src="'.$path.'grey.jpg" width="'.$size.'" />';
                break;
            case 9: // results sent
                $light = '<img src="'.$path.'grey.jpg" width="'.$size.'" />';
                break;
        }
        return $light;
    }
    /*
     * 1st sets the start time
     * sets status for the race to 5
     */

    private function update_status($status){
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_races set status = '.$status.' where ID = '.$this->races_ID;
        $result = $wpdb->query($sql);
        $this->status = $status;
    }
    private function update_result_status(){
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_races set results_sys = 3 where ID = '.$this->races_ID;
        $result = $wpdb->query($sql);
    }
}
