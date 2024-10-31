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

class prm_Results extends WP_List_Table {
    var $courses_ID;
    var $races_ID;
    var $race; // object
    var $list_for;
    var $where;
    /*
     * list_for = race or course
     * corresponding ID must be set, if both IDs are empty and error will be returned
     */

    function __construct($list_for,$races_ID=NULL,$courses_ID=NULL){
        $this->list_for = $list_for;
        $this->courses_ID = $courses_ID;
        $this->races_ID = $races_ID;
        // set where for sql
        $this->where = ($list_for == 'race')? ' where races_id = '.$this->races_ID.' order by race_placing ASC' :
                       ' where races_id = '.$this->courses_ID .' order by course_placing ASC';

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'racer',     //singular name of the listed records
            'plural'    => 'racers',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        include_once(PRM_PUGIN_PATH.'classes/prm-racers.php');
        include_once(PRM_PUGIN_PATH.'classes/prm-races.php');
        $this->race = new prm_Races($this->races_ID);
    }
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
       if ( $which == "top" ){
       }

       if ( $which == "bottom" ){
       }
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        switch($column_name){
            case 'bib_number':
            case 'gender':
            case 'last_name' :
            case 'race_placing';
                return $item->$column_name;

        }
    }
    //each column needs a method to render it
    function column_first_name($item){
        //Return competitor first name
        return sprintf('%1$s',
            '<strong>'.$item->first_name.'</strong>'
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

    //list the columns with their names
    function get_columns(){

        $columns = array(
            'race_placing' => 'Rank',
            'first_name'     => 'First Name',
            'last_name' => 'Last Name',
            'bib_number' => 'Bib#',
            'course' => 'Course',
            'race_time' => 'Time'

        );
        return $columns;
    }
    // set which cloumns are sortable
    function get_sortable_columns() {
            $sortable_columns = array(

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
                       on c.competitors_ID = r.competitors_ID'.$this->where;


       /* -- Ordering parameters -- */
           //Parameters that are going to be used to order the result
           $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
           $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
           if(!empty($orderby) & !empty($order)){ $sql.=' ORDER BY '.$orderby.' '.$order; }

       /* -- Pagination parameters -- */
            //Number of elements in your table?
            $totalitems = $wpdb->query($sql); //return the total number of affected rows
            //How many to display per page?
            $perpage = 100;
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