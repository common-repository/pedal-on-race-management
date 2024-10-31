<?php
/**
 * list the racers of an event
 * Date: 26/08/14
 * Time: 9:15 PM
 * This is the code to extend the core WP_List_Table class
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class prm_Live_Results extends WP_List_Table {
    var $races_ID;
    var $events_ID;
    var $handed_in_where;
    var $where;

    /*
     * where = what object to list by (events or races)
     * events_ID and races_ID - one of these must be handed in
     * if races_ID is set events_ID will be looked up
     */
    function __construct($races_ID=NULL,$where,$events_ID=NULL){

        $this->handed_in_where = $where;
        $this->races_ID = (!is_null($races_ID))? $races_ID : NULL;

        if(!is_null($events_ID)){
            $this->events_ID = $events_ID;
        }else{

            $this->races_ID = $races_ID;
            include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $my_race = new prm_Races($races_ID);
            $this->events_ID = $my_race->event_ID;
        }
        // build sql where
        switch($where){
            case 'events':
                $this->where = ' where events_ID = '.$this->events_ID;
                include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
                $event = new prm_Event($this->events_ID);
                $event_name = $event->get_name();
                break;
            case 'races':
                $this->where = ' where races_ID = '.$this->races_ID;
                include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
                //$race = new prm_Races($this->races_ID);
                $race_name = prm_Races::get_var('name',$this->races_ID);
                if(is_null($this->events_ID)){ // need to set
                    $this->events_ID = prm_Races::get_var('events_ID',$this->races_ID);
                }
                if(empty($event_name)){
                    include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
                    $event = new prm_Event($this->events_ID);
                    $event_name = $event->get_name();
                }
                break;
        }

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'racer',     //singular name of the listed records
            'plural'    => 'racers',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
    }
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
       if ( $which == "top" ){
           // lets dispaly a header
           include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
           $event = new prm_Event($this->events_ID);
           $event_name = $event->get_name();
           echo '<h2>'. ucfirst($event_name) .'</h2>';
       }
       if ( $which == "bottom" ){
          //The code that goes after the table is there
           echo '* is progress time and ^ is the un-official finish time<BR>';
           echo prm_Racer::lights(1);
       }
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        // mark row if returning from edit

        switch($column_name){
            case 'bib_number':
            case 'gender':
            case 'last_name' :
                return $item->$column_name;
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    //each column needs a method to render it
    function column_status($item){ //return status light
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        $racer = new prm_Racer($item->ID);
        $light = $racer->status();
        return $light;
    }
    function column_first_name($item){
        //Return competitor first name
        return sprintf('%1$s',
            $item->first_name
        );
    }
    function column_race_time($item){ // show race time if racing (on course)
        if($item->status == 5){ // return progress time
            include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $race = new prm_Races($item->races_ID);
            $race_time = $race->race_clock();
            if($race_time) return $race_time.'*';
            else return 'TBA';
        }elseif($item->status == 7){
            $racer = new prm_Racer($item->ID);
            $result = $racer->calc_result();
            if($result) return $result.'^';
        }
    }
    function column_course($item){
        if($this->handed_in_where == 'events'){ // only show if displaying All
            //need to get courses_ID
            include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $courses_id = prm_Races::get_var('courses_ID',$item->races_ID);
            include_once(PRM_PUGIN_PATH.'classes/prm-courses.php');
            $course = new prm_Courses($courses_id);
            $short_name = $course->get_name(1);
            return $short_name;
        }
    }

    //list the columns with their names
    function get_columns(){
        $columns = array(

            'first_name'     => 'First Name',
            'last_name' => 'Last Name',
            'status' => 'Status',
            'course' => 'Course',
            'bib_number' => 'Bib#',
            'race_time' => 'Time'
        );
        return $columns;
    }
    // set which cloumns are sortable
    function get_sortable_columns() {
            $sortable_columns = array(
                'first_name' => array('first_name',false),
                'last_name' => array('last_name',false),
                'status'     => array('status',false) ,    //true means it's already sorted
                'bib_number' => array('bib_number',false)
            );
            return $sortable_columns;
        }
    //Prepare the table with different parameters, pagination, columns and table elements
    function prepare_items() {
       global $wpdb;

       /* -- Preparing your query -- */
        //$where = ($this->action == 'event') ? ' where events_ID = '.$this->event_ID : ' where races_ID = '.$this->race_ID;
        $sql = 'select r.*,c.first_name, c.last_name, c.gender from '.
                       $wpdb->prefix.'prm_racers r left join '. $wpdb->prefix .'prm_competitors c
                       on c.competitors_ID = r.competitors_ID'.
                       $this->where .' and r.status between 3 and 7 ';

       /* -- Ordering parameters -- */
           //Parameters that are going to be used to order the result
           $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
           $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
           if(!empty($orderby) & !empty($order)){ $sql.=' ORDER BY '.$orderby.' '.$order; }

       /* -- Pagination parameters -- */
            //Number of elements in your table?
            $totalitems = $wpdb->query($sql); //return the total number of affected rows
            //How many to display per page?
            $perpage = 50;
            //Which page is this?
            $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
            //Page Number
            if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
            //How many pages do we have in total?
            $totalpages = ceil($totalitems/$perpage);
            //adjust the query to take pagination into account
           if(!empty($paged) && !empty($perpage)){
              $offset=($paged-1)*$perpage;
             $sql.=' LIMIT '.(int)$offset.','.(int)$perpage;
           }

       /* -- Register the pagination -- */
          $this->set_pagination_args( array(
             "total_items" => $totalitems,
             "total_pages" => $totalpages,
             "per_page" => $perpage,
          ) );
          //The pagination links are automatically built according to those parameters

       /* -- Register the Columns -- */
          $columns = $this->get_columns();
          $hidden = array();
          $sortable = $this->get_sortable_columns();
          $this->_column_headers = array($columns, $hidden, $sortable);
       /* -- Fetch the items -- */
          $this->items = $wpdb->get_results($sql);
    }
} // EOF class
?>