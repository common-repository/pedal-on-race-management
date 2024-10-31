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

class prm_Competitors_List extends WP_List_Table {
    //var $list_ID; // this is either the event or course ID for which to list racers
    //var $list_field; // needs to be field to list by (course_ID or event_ID)
    var $updated_ID;
    var $green;
    function __construct($updated_ID= NULL,$event_ID=NULL){
        $this->updated_ID = $updated_ID;
        $this->green = ' style="color: green;"';
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'competitor',     //singular name of the listed records
            'plural'    => 'competitors',    //plural name of the listed records
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
           echo '<h2>Competitors List</h2>';
           echo('<a href="?page=prm-competitors&m=m&o=competitors&a=add&f=2l">Add new Competitor</a>');
       }
       /*if ( $which == "bottom" ){
          //The code that goes after the table is there

       }*/
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        $green = ($this->updated_ID == $item->competitors_ID)? $this->green : '';
        switch($column_name){
            case 'last_name':
            case 'competitors_type':
            case 'gender':
            case 'email':
            case 'birth_date':
                return '<span '. $green .'>'.$item->$column_name.'</span>';
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
        }
    //each column needs a method to render it

    function column_first_name($item){
        // mark row if returning from edit
        $green = ($item->competitors_ID == $this->updated_ID) ? ' style="color: green;"':'';
        //Build row actions
        $actions = array(
            'View'      => sprintf('<a href="?page=%s&m=v&o=competitors&a=%s&i=%s&f=2m">View</a>',$_REQUEST['page'],'view',$item->competitors_ID)
        );
        //Return competitor first name

        return sprintf('%1$s %2$s',
            '<span '. $green .' >'.$item->first_name.'</span>',
            $this->row_actions($actions)
        );
    }

    //this is the check box cloumn
    function column_cb($item){
            return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
                /*$2%s*/ $item->competitors_ID                //The value of the checkbox should be the record's id
            );
        }
    //list the columns with their names
    function get_columns(){
            $columns = array(
                'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
                'first_name'     => 'First',
                'last_name' => 'Last',
                'competitors_type' => 'Status',
                'gender' => 'Gender',
                'email' => 'Email',

                'birth_date' => 'DOB'
            );
            return $columns;
        }
    // set which cloumns are sortable
    function get_sortable_columns() {
            $sortable_columns = array(
                'first_name' => array('first_name',true),
                'last_name' => array('last_name',false),
                'bib' => array('bib',false)
            );
            return $sortable_columns;
        }
    //Prepare the table with different parameters, pagination, columns and table elements
    function prepare_items() {
       global $wpdb;

       /* -- Preparing your query -- */
       $sql = 'select * from '.$wpdb->prefix.'prm_competitors order by first_name';

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