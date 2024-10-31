<?php
/**
 * have switch for non-race events so I can use on dungogevents
 */
class prm_Event{
    var $event_ID;
    var $table_name;
    var $event; // event properties (table columns) other than event_status
    var $status; //this can change before DB is re-read
    function __construct($event_ID){
        global $wpdb;
        $this->event_ID = $event_ID;
        $this->table_name = $wpdb->prefix.'prm_events';
        $sql = 'select * from '. $this->table_name .' where ID = '.$event_ID;
        $this->event = $wpdb->get_row($sql);
        $this->status = $this->event->status;
        //check if event is sold out
        $this->check_status();
    }
    public function archive(){
        global $wpdb;
        $sql = 'update '.$this->table_name.'
                set status = 9 where ID = '.$this->event_ID;
        $result = $wpdb->query($sql);
        return $result;
    }
    public function bread_crumb(){
        $bread_crumb = '<a href="'.PRM_ADMIN_URL.'prm-events">Events</a> > <a href="'.PRM_ADMIN_URL.'prm-events&action=view&event='.$this->event->ID.'" >'.$this->event->name.'</a>';
        return $bread_crumb;
    }
    /*
     * all statuses bar sold out(5) are set by user
     * don't check if status is >= 7 since the event has been closed
     */
    private function check_status(){
        if($this->status < 7){
            $max_entries = $this->sum('races','max_entries');
            if($max_entries == 0){ // seems no race(s) have been setup as yet, so let's not change status
                // do nothing
            }else{ // seems we have a list ione race
                //need to know how many entries we have
                $racers = $this->number('racers');
                if($racers >= $max_entries){ //we are sold out
                    $this->update_status(5);
                    $this->status = 5;
                }
            }
        }
    }
    public function close_entries(){ // need to set status to
        $this->update_status(5);
        $this->status = 5;
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
        prm_View::object_list('events',$this->event_ID,14,$this->event_ID);

    }
    public function close_event(){
        global $wpdb;
        // check if all races have finished
        $sql = 'select count(*) as races from '.$wpdb->prefix.'prm_races
               where status < 7 and events_ID = '.$this->event_ID;
        $row = $wpdb->get_row($sql);
        $races = $row->races;
        if($races > 0){ //not all races have been closed
            include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
            prm_View::object_list('events',$this->event_ID,15,$this->event_ID);
        }else{ // lets close event
            $this->update_status(7);
            $this->status = 7;
            include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
            prm_View::object_list('events',$this->event_ID,14,$this->event_ID);
        }
    }
    public static function lights(){ //return a list of all lights and their meaning
        $path = PRM_PLUGIN_URL.'images/';
        $lights = '<img src="'.$path.'orange.jpg" width="12" /> Entries not yet open  ';
        $lights .= ' <img src="'.$path.'green.jpg" width="12" /> Entries open  ';
        $lights .= ' <img src="'.$path.'red.jpg" width="12" /> Entries closed or Sold out  ';
        $lights .= ' <img src="'.$path.'grey.jpg" width="12" /> Event closed';
        return $lights;
    }
    public function get_name(){
        return $this->event->name;
    }
    /*
     * returns the sum of children eg courses of an event
     * of any table that contains events_ID eg
     * races, courses, entries, legs, crew
     * provide without prefixes
     */
    public function number($of_table){
        global $wpdb;
        $sql = 'select count(*) as number_of from '.$wpdb->prefix.'prm_'.$of_table.' where events_ID = '.$this->event_ID;
        $row = $wpdb->get_row($sql);
        // return number of or 0 if none or false if query fails
        return ($row->number_of > 0) ? $row->number_of :  0;
    }
    public function open_entries(){ // need to set status to
        $this->update_status(3);
        $this->status = 3;
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
        prm_View::object_list('events',$this->event_ID,13,$this->event_ID);

    }
    public function sum($table,$column){ //get sum of a column
        global $wpdb;
        $sql = 'select sum('.$column.') as sum_of from '.$wpdb->prefix.'prm_'.$table.' where events_ID = '.$this->event_ID;
        $row = $wpdb->get_row($sql);
        // return number of or 0 if none or false if query fails
        return ($row->sum_of > 0) ? $row->sum_of :  0;
    }
    public function status($size=30){ //return relevant status light, size can be set
        $path = PRM_PLUGIN_URL.'images/';
        switch ($this->status){

            case 0:
                $light = '<img src="'.$path.'orange.jpg" width="'.$size.'" />';
            break;
            case 3:
                $light = '<img src="'.$path.'green.jpg" width="'.$size.'" />';
            break;
            case 5:
                $light = '<img src="'.$path.'red.jpg" width="'.$size.'" />';
            break;
            case 7:
                $light = '<img src="'.$path.'grey.jpg" width="'.$size.'" />';
            break;
        }
        return $light;
    }
    private function update_status($status){
        global $wpdb;
        $sql = 'update '.$wpdb->prefix.'prm_events set status = '.$status.' where ID = '.$this->event_ID;
        $result = $wpdb->query($sql);
    }
}
?>