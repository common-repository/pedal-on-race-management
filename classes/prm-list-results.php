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

class prm_Results_List extends WP_List_Table {
    var $updated_ID;
    var $green;
    var $races_ID;
    var $race; // object
    var $results_status; // 3 = placings processed

    /*
     * where = what object to list by (events or races)
     * type = reason for listing (on-course, entries, regos, results)
     * events_ID and races_ID - one of these must be handed in
     * header = the heading for the racers list
     * if races_ID is set events_ID will be looked up
     */
    function __construct($races_ID,$updated_ID= NULL){
        $this->updated_ID = $updated_ID;

        $this->races_ID = $races_ID;
        $this->green = ' style="color: green;"';

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'racer',     //singular name of the listed records
            'plural'    => 'racers',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
        $this->race = new prm_Races($this->races_ID);
        $this->results_status = $this->race->race->results_sys;
    }
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
       if ( $which == "top" ){
          //The code that goes before the table is here
           echo '<h2>'. ucfirst($this->race->race->name) .'</h2>';
           echo '<a href="?page=prm-events&m=c&o=races&a=placings&i='. $this->races_ID .'&e='. $this->race->event_ID .'&f=3l" onclick="return confirm(\'Have you checked all results?\');">Process Placings</a>  |  ';
           echo '<a href="?page=prm-events&m=c&o=races&a=publish&i='. $this->races_ID .'&e='. $this->race->event_ID .'&f=3l" onclick="return confirm(\'Have you checked the placings?\');">Publish Results</a>';
       }

       if ( $which == "bottom" ){
          //The code that goes after the table is there
           echo prm_Racer::result_lights();
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
            case 'race_placing';
                return '<span '. $green .' >'.$item->$column_name.'</span>';
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    //each column needs a method to render it
    function column_status($item){ //return status
        $finish = $item->result_type;
        switch($finish){
            case 'FIN':
                return '<span style="color: green;">'. $finish .'</span>';
                break;
            case 'DNF':
                return '<span style="color: green;">'. $finish .'</span>';
                break;
        }

        /*include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        $racer = new prm_Racer($item->ID);
        $light = $racer->result_status();
        return $light;*/
    }
    function column_first_name($item){
        echo '<style type="text/css">';
                echo '.wp-list-table .column-first_name { width: 25%; }';
                echo '</style>';
        // mark row if returning from edit
        $green = ($item->ID == $this->updated_ID) ? ' style="color: green;"':'';
        //Build row actions

        $actions = array(
            'View'   => sprintf('<a href="?page=%s&m=v&o=racers&a=%s&i=%s&f=3m">View</a>',$_REQUEST['page'],'view',$item->ID)
        );


        //Return competitor first name
        return sprintf('%1$s %2$s',
            '<span '. $green .' >'.$item->first_name.' (id:'.$item->ID.')</span>',
            $this->row_actions($actions)
        );
    }
    function column_race_time($item){
        $racer = new prm_Racer($item->ID);
        return $racer->get_race_time();

    }
    function column_course($item){
            //need to get courses_ID
            //include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
            $courses_id = prm_Races::get_var('courses_ID',$item->races_ID);
            include_once(PRM_PUGIN_PATH.'classes/prm-courses.php');
            $course = new prm_Courses($courses_id);
            $short_name = $course->get_name(1);
            return $short_name;
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
            'first_name'     => 'First Name',
            'last_name' => 'Last Name',
            'bib_number' => 'Bib#',
            'status' => 'Finish',
            'course' => 'Course',
            'race_time' => 'Time',
            'race_placing' => 'Rank'
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
        $new_order = ($this->results_status == 3)? ' order by race_placing ASC' : '';

        $sql = 'select r.*,c.first_name, c.last_name, c.gender from '.
                       $wpdb->prefix.'prm_racers r left join '. $wpdb->prefix .'prm_competitors c
                       on c.competitors_ID = r.competitors_ID
                       where races_ID = '.$this->races_ID.$new_order;

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