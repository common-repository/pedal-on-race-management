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

class prm_Racers_List extends WP_List_Table {
    var $updated_ID;
    var $green;
    var $races_ID;
    var $events_ID;
    var $handed_in_where;
    var $where;
    var $type;

    /*
     * where = what object to list by (events or races)
     * type = reason for listing (on-course, entries, regos, results)
     * events_ID and races_ID - one of these must be handed in
     * header = the heading for the racers list
     * if races_ID is set events_ID will be looked up
     */
    function __construct($races_ID=NULL,$where,$type,$events_ID=NULL,$updated_ID= NULL){
        $this->updated_ID = $updated_ID;
        $this->type = $type;
        $this->handed_in_where = $where;
        $this->races_ID = (!is_null($races_ID))? $races_ID : NULL;
        $this->green = ' style="color: green;"';
        if(!is_null($events_ID)){
            $this->events_ID = $events_ID;
        }else{
            if(is_null($races_ID)) if(PRM_DEBUG == 'Yes') die('events or races ID must be set');
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
        switch($type){ // add condition to where depending on type switch
            case 'entries':
                // no need to set anything since we want to see all racers
                $this->header = 'Competitors Entered in '.$event_name;
                if($where == 'races') $this->header .= ' for '.$race_name;
                break;
            case 'regos':
                $this->where .= ' and status = 1';
                $this->header = 'Competitors Registered in '.$event_name;
                if($where == 'races') $this->header .= ' for '.$race_name;
                break;
            case 'on-course':
                $this->where .= ' and status BETWEEN 4 and 5 ';
                $this->header = 'Competitors On Course in '.$event_name;
                if($where == 'races') $this->header .= ' for '.$race_name;
                break;
            case 'results':
                $this->where .= ' and status = 6 or status = 7';
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
          //The code that goes before the table is here
           echo '<h2>'. $this->header .'</h2>';
           //menu options
           echo '<a href="?page=prm-events&m=v&o=races&a=list&e='. $this->events_ID .'&f=7l">Back to Race Central</a> | '.
                '<a href="?page=prm-events&m=m&o=racers&a=add&i='. $this->races_ID .'&e='. $this->events_ID .'&f=7l">Add Competitor</a>';
       }

       if ( $which == "bottom" ){
          //The code that goes after the table is there
           echo prm_Racer::lights();
       }
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        // mark row if returning from edit
        $green = ($item->ID == $this->updated_ID) ? ' style="color: green;"':'';
        switch($column_name){
            case 'bib_number':
            case 'gender':
            case 'last_name' :
                return '<span '. $green .' >'.$item->$column_name.'</span>';
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
        echo '<style type="text/css">';
                echo '.wp-list-table .column-first_name { width: 25%; }';
                echo '</style>';
        // mark row if returning from edit
        $green = ($item->ID == $this->updated_ID) ? ' style="color: green;"':'';
        //Build row actions
        switch($item->status){
            case '0': // entered
                $actions = array(
                    'Rego'   => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">Rego</a>',$_REQUEST['page'],'rego',$item->ID),
                    'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">View</a>',$_REQUEST['page'],'view',$item->ID),
                    'Remove'   => sprintf('<a href="?page=%s&m=m&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">Remove</a>',$_REQUEST['page'],'del',$item->ID)
                );
                break;
            case '1': // registered
                $actions = array(
                    'Sign-in'   => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">Sign-in</a>',$_REQUEST['page'],'sign-in',$item->ID),
                    'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">View</a>',$_REQUEST['page'],'view',$item->ID),
                    'Remove'   => sprintf('<a href="?page=%s&m=m&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">Remove</a>',$_REQUEST['page'],'del',$item->ID)
                );
                break;
            case '3': // signed in
                $actions = array(
                    'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">View</a>',$_REQUEST['page'],'view',$item->ID),
                    'Remove'   => sprintf('<a href="?page=%s&m=m&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">Remove</a>',$_REQUEST['page'],'del',$item->ID)
                );
                break;
            case '5': // racing
                $actions = array(
                    'FIN'   => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">Finish</a>',$_REQUEST['page'],'FIN',$item->ID),
                    'DNF'   => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">DNF</a>',$_REQUEST['page'],'DNF',$item->ID),
                    'DQ'   => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">DQ</a>',$_REQUEST['page'],'DQ',$item->ID),
                    'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case '7': // finished
                $actions = array(
                    'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case '8': // result published
            case '9': // result sent
                $actions = array(
                    'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&e='. $this->events_ID .'&i=%s&f=7m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
        }
        //check if they should have a bib number
        $no_bib = '';
        if($item->status >= 1 && is_null($item->bib_number)) $no_bib = '<span style="color: red";> No bib number!</span>';
        //Return competitor first name
        return sprintf('%1$s %2$s',
            '<span '. $green .' >'.$item->first_name.' (id:'.$item->ID.')</span>'.$no_bib,
            $this->row_actions($actions)
        );
    }
    function column_race_time($item){ // show race time if racing (on course)
        if($this->type == 'on-course'){
            include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $race = new prm_Races($item->races_ID);
            $race_time = $race->race_clock();
            if($race_time) return $race_time;
            else return 'not started';
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
    //this is the check box cloumn
    function column_cb($item){
            return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
                /*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
            );
        }
    //list the columns with their names
    function get_columns(){
        if($this->handed_in_where == 'events'){ // add column course when listing all races
            if($this->type == 'on-course'){
                $columns = array(
                    'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
                    'first_name'     => 'First Name',
                    'last_name' => 'Last Name',
                    'status' => 'Status',
                    'course' => 'Course',
                    'bib_number' => 'Bib#',
                    'race_time' => 'Time'
                );
            }else{
                $columns = array(
                    'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
                    'first_name'     => 'First Name',
                    'status' => 'Status',
                    'last_name' => 'Last Name',
                    'course' => 'Course',
                    'bib_number' => 'Bib#'
                );
            }
        }else{
            if($this->type == 'on-course'){
                $columns = array(
                    'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
                    'first_name'     => 'First Name',
                    'last_name' => 'Last Name',
                    'status' => 'Status',
                    'gender' => 'Gender',
                    'bib_number' => 'Bib#',
                    'race_time' => 'Time'
                );
            }else{
                $columns = array(
                    'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
                    'first_name'     => 'First Name',
                    'status' => 'Status',
                    'last_name' => 'Last Name',
                    'gender' => 'Gender',
                    'bib_number' => 'Bib#'
                );
            }
        }
        return $columns;
    }
    // set which cloumns are sortable
    function get_sortable_columns() {
            $sortable_columns = array(
                'first_name' => array('first_name',false),
                'last_name' => array('last_name',false),
                'status'     => array('status',false) ,    //true means it's already sorted
                'bib' => array('bib',false)
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
                       $this->where;

       /* -- Ordering parameters -- */
           //Parameters that are going to be used to order the result
           $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
           $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
           if(!empty($orderby) & !empty($order)){ $sql.=' ORDER BY '.$orderby.' '.$order; }

       /* -- Pagination parameters -- */
            //Number of elements in your table?
            $totalitems = $wpdb->query($sql); //return the total number of affected rows
            //How many to display per page?
            $perpage = 25;
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