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

class prm_Courses_List extends WP_List_Table {
    //var $list_ID; // this is either the event or course ID for which to list racers
    //var $list_field; // needs to be field to list by (course_ID or event_ID)
    var $updated_ID;
    var $green;
    function __construct($updated_ID= NULL,$event_ID=NULL){
        $this->updated_ID = $updated_ID;
        $this->green = ' style="color: green;"';

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'course',     //singular name of the listed records
            'plural'    => 'courses',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
    }
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
       if ( $which == "top" ){
          //The code that goes before the table is here
           echo '<h2>Course Management</h2>';
           echo('<a href="?page=prm-courses&m=m&o=courses&a=add&f=4l">Add new Course</a>');
       }
       /*if ( $which == "bottom" ){
          //The code that goes after the table is there

       }*/
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        switch($column_name){
            case 'course_usage_type':
            case 'short_name':
            case 'access':
                return '<span '. $green .'>'.$item->$column_name.'</span>';
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
        }
    //each column needs a method to render it
    function column_length($item){ // show length with UOM
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        $length = $item->length.' '.$item->measurement_type;
        return '<span '. $green .'>'.$length .'</span>';
    }

    function column_name($item){
        // mark row if returning from edit
        $green = ($item->ID == $this->updated_ID) ? ' style="color: green;"':'';
        //Build row actions
        $actions = array(
            'View'      => sprintf('<a href="?page=%s&m=v&o=courses&a=%s&i=%s&f=4m">View</a>',$_REQUEST['page'],'view',$item->ID)
        );
        //Return competitor first name

        return sprintf('%1$s %2$s',
            '<span '. $green .' >'.$item->name.' (id:'. $item->ID .')</span>',
            $this->row_actions($actions)
        );
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
                'name' => 'Name',
                'short_name' => 'Short',
                'course_usage_type'     => 'Type',
                'length' => 'Length',
                'access' => 'Access'

            );
            return $columns;
        }
    // set which cloumns are sortable
    /*function get_sortable_columns() {
            $sortable_columns = array(
                'first_name' => array('first_name',true),
                'last_name' => array('last_name',false),
                'status'     => array('name',false) ,    //true means it's already sorted
                'bib' => array('bib',false)
            );
            return $sortable_columns;
        }*/
    //Prepare the table with different parameters, pagination, columns and table elements
    function prepare_items() {
       global $wpdb;

       /* -- Preparing your query -- */
       $sql = 'select * from
              '.$wpdb->prefix.'prm_courses';

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