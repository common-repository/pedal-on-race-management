<?php
/**
 * User: wolfskafte-zauss
 * Date: 26/08/14
 * Time: 9:15 PM
 * This is the code to extend the core WP_List_Table class
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class prm_Races_List extends WP_List_Table {
    var $updated_ID;
    var $green;
    var $event_ID;

    function __construct($updated_ID= NULL,$event_ID=NULL){
        $this->updated_ID = $updated_ID;
        $this->green = ' style="color: green;"';
        $this->event_ID = $event_ID;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'race',     //singular name of the listed records
            'plural'    => 'races',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
    }
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
       if ( $which == "top" ){
           include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
           $event = new prm_Event($this->event_ID);
           echo '<h2>'. $event->get_name() .' - Race Central</h2>';
           echo 'Race Functions: '.
               '<a href="?page='. $_REQUEST['page'] .'&m=m&o=races&a=add&e='. $this->event_ID .'&f=6l">Add New Race</a> | '.
               //'<a href="?page='. $_REQUEST['page'] .'&m=m&o=racers&a=add&e='. $this->event_ID .'&f=6l&events_ID='. $this->event_ID .'">Add Competitor</a> | '.
              // '<a href="?page='. $_REQUEST['page'] .'&m=v&o=races&a=list&e='. $this->event_ID .'&f=6l">List All Races</a> | '.
               '<a href="?page='. $_REQUEST['page'] .'&m=c&o=racers&a=list&e='. $this->event_ID .'&f=6l&where=events&type=entries">Show All Entries</a> | '.
               '<a href="?page='. $_REQUEST['page'] .'&m=c&o=racers&a=list&e='. $this->event_ID .'&f=6l&where=events&type=on-course">Show All On Course</a>';
       }
       if ( $which == "bottom" ){
          //The code that goes after the table is there
           echo 'Meaning of lights: '.prm_Races::lights();
       }
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        switch($column_name){
            case 'director':
                return '<span '. $green .'>'.$item->$column_name.'</span>';
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    //each column needs a method to render it

    function column_name($item){
        echo '<style type="text/css">';
                echo '.wp-list-table .column-name { width: 25%; }';
                echo '</style>';
        // we need to ensure that races is set to correct status, initiate object, this will check and unset
        $my_race = new prm_Races($item->ID); //unset is at end of methos just incase we need race info

        // mark row if returning from edit
        $green = ($item->ID == $this->updated_ID) ? ' style="color: green;"':'';
        //Build row actions
        switch($my_race->status){
            case 0: // awaiting sig-ins
                $actions = array(
                    'Entries' => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&i=%s&f=6m&where=races&type=entries">Entries</a>',$_REQUEST['page'],'list',$item->ID),
                    'View'      => sprintf('<a href="?page=%s&m=v&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case 3: // Sign-in in progress
                $actions = array(
                    'Entries' => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&i=%s&f=6m&where=races&type=entries">Entries</a>',$_REQUEST['page'],'list',$item->ID),
                    'Start'      => sprintf('<a href="?page=%s&m=c&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">Start</a>',$_REQUEST['page'],'start',$item->ID),
                    'View'      => sprintf('<a href="?page=%s&m=v&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case 5: //Race in progress
                $actions = array(
                    'On-Course' => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&i=%s&f=6m&where=races&type=on-course">On Course</a>',$_REQUEST['page'],'list',$item->ID),
                    'Entries' => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&i=%s&f=6m&where=races&type=entries">Entries</a>',$_REQUEST['page'],'list',$item->ID),
                    'View'      => sprintf('<a href="?page=%s&m=v&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case 7: //Race finished
                $actions = array(
                    'Results'=> sprintf('<a href="?page=%s&m=c&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">Results</a>',$_REQUEST['page'],'results',$item->ID),
                    'View'      => sprintf('<a href="?page=%s&m=v&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">View</a>',$_REQUEST['page'],'view',$item->ID),
                    'Entries' => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&i=%s&f=6m&where=races&type=entries">Entries</a>',$_REQUEST['page'],'list',$item->ID)
                );
                break;
            case 8: // results published, implement for Pro
            case 9: // Results email sent
                $actions = array(
                    'View'      => sprintf('<a href="?page=%s&m=v&o=races&a=%s&i=%s&e='. $this->event_ID .'&f=6m">View</a>',$_REQUEST['page'],'view',$item->ID),
                    'Entries' => sprintf('<a href="?page=%s&m=c&o=racers&a=%s&i=%s&f=6m&where=races&type=entries">Entries</a>',$_REQUEST['page'],'list',$item->ID)
                );
                break;
        }
        unset($my_race);
        //Return race name

        return sprintf('%1$s %2$s',
            '<span '. $green .' >'.$item->name.' (id:'. $item->ID .')</span>',
            $this->row_actions($actions)
        );
    }
    function column_course($item){ //list course name
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        include_once(PRM_PUGIN_PATH.'classes/prm-courses.php');
        $course = new prm_Courses($item->courses_ID);
        $course_name = $course->get_name(1);
        unset($course);
        return '<span '. $green .'>'.$course_name.'</span>';
    }
    function column_status($item){
        $race = new prm_Races($item->ID);
        $light = $race->status();
        return $light;
    }
    function column_entries($item){
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        $race_entries = prm_Races::get_entries($item->ID);
        $entries = $race_entries.' / '.$item->max_entries;
        return '<span '. $green .'>'.$entries.'</span>';
    }
    function column_race_date_time($item){
        if(!is_null($item->race_date_time)){
            $green = ($this->updated_ID == $item->ID)? $this->green : '';
            $race_start = date_create($item->race_date_time);
            $race_start = date_format($race_start, 'H:i '. PRM_DATE_FORMAT);
        }else $race_start = 'not set';
        return '<span '. $green .'>'.$race_start.'</span>';
    }
    function column_clock($item){ //show elapsed race time
        $race = new prm_Races($item->ID);
        $race_clock = $race->race_clock();
        return $race_clock;
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
            $columns = array(
                'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
                'name'     => 'Name',
                'status' => 'Status',
                'director' => 'Director',
                'course' => 'Course',
                'entries' => 'Entries',
                'race_date_time' => 'Race Start',
                'clock' => 'Race Clock'
            );
            return $columns;
        }
    // set which cloumns are sortable
    function get_sortable_columns() {
            $sortable_columns = array(
                'name' => array('name',true),
                'status' => array('status',false)
            );
            return $sortable_columns;
        }
    //Prepare the table with different parameters, pagination, columns and table elements
    function prepare_items() {
       global $wpdb;

       /* -- Preparing your query -- */
       $sql = 'select r.*,concat_ws(" ",c.first_name,c.last_name) as director from '.
               $wpdb->prefix.'prm_races r left join '. $wpdb->prefix .'prm_crew c
               on c.ID = r.crew_ID
               where events_ID = '.$this->event_ID;

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