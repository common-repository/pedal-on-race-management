<?php
/**
 *
 */
class prm_Courses{
    var $course_ID;
    var $table_name;
    public $course; // properties (table columns)
    var $status; //need this here to update class else there's a lag to next DB read
    function __construct($course_ID){
        global $wpdb;
        $this->course_ID = $course_ID;
        $this->table_name = $wpdb->prefix.'prm_courses';
        $sql = 'select * from '. $this->table_name .' where ID = '.$course_ID;
        $this->course = $wpdb->get_row($sql);
        $this->status = $this->course->status;
        $this->check_status();
    }
    private function check_status(){ //depending on current status check if the next status has been reached
        if($this->status > 6){ //is closed or archieved
            //do nothing
        }else{
            switch($this->status){
                case 0: //no regos
                    //need to change to 3 if regos exist
                    $racers_status = $this->sum('racers','status');
                    if($racers_status >= 3){ //we have at lease one racer registered
                        $fields = array();
                        $fields['status'] = 3;
                        $this->update($fields);
                        $this->status = 3;
                    }
                break;
                /*case 3: //entries open, need to check if sold out
                    $entries_available = $this->sum('courses','max_entries');
                    $entries = $this->number('racers');
                    if($entries >= $entries_available){ // we're sold out
                        $fields = array();
                        $fields['status'] = 5;
                        $this->update($fields);
                        $this->status = 5;
                    }
                break;
                case 5: //check if all courses are closed (status 7)
                    $courses = $this->number('courses');
                    $sum_of_status = $this->sum('courses','status');
                    if($sum_of_status >= $courses * 7){ //all race courses are closed
                        $fields['status'] = 7;
                        $this->update($fields);
                        $this->status = 7;
                    }
                break;*/
            }
        }
    }
    /*
     * returns the sum of children eg courses of an event
     * of any table that contains events_ID eg
     * races, courses, entries, legs, crew
     * provide without prefixes
     */
    public function number($of_table){
        global $wpdb;
        $sql = 'select count(*) as number_of from '.$wpdb->prefix.'prm_'.$of_table.' where courses_ID = '.$this->course_ID;
        $row = $wpdb->get_row($sql);
        // return number of or 0 if none or false if query fails
        return ($row->number_of > 0) ? $row->number_of :  0;
    }
    public function sum($table,$column){ //get sum of a column
        global $wpdb;
        $sql = 'select sum('.$column.') as sum_of from '.$wpdb->prefix.'prm_'.$table.' where courses_ID = '.$this->course_ID;
        $row = $wpdb->get_row($sql);
        // return number of or 0 if none or false if query fails
        return ($row->sum_of > 0) ? $row->sum_of :  0;
    }
    public function get_name($short=NULL){ // set short =1 to return short name
        if($short) return $this->course->short_name;
        else return $this->course->name;
    }
    public function status($size=30){ //return relevant status light, size can be set
        $path = PRM_PLUGIN_URL.'images/';
        switch ($this->status){
            case 0:
                $light = '<img src="'.$path.'grey.jpg" width="'.$size.'" />';
            break;
            case 3:
                $light = '<img src="'.$path.'orange.jpg" width="'.$size.'" />';
            break;
            case 5:
                $light = '<img src="'.$path.'green.jpg" width="'.$size.'" />';
            break;
            case 7:
                $light = '<img src="'.$path.'red.jpg" width="'.$size.'" />';
            break;
        }
        return $light;
    }
}
?>