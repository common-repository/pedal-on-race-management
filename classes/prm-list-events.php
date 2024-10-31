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

class prm_Events_List extends WP_List_Table {
    var $prm_event;
    var $updated_ID;
        var $green;
        function __construct($updated_ID= NULL){
            $this->updated_ID = $updated_ID;
            $this->green = ' style="color: green;"';
            //Set parent defaults
            parent::__construct( array(
                'singular'  => 'event',     //singular name of the listed records
                'plural'    => 'events',    //plural name of the listed records
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
           echo '<h2>Events Management</h2>';
           echo('<a href="?page=prm-events&m=m&o=events&a=add&f=5l&cf=events">Add new Event</a>');
       }
       if ( $which == "bottom" ){
          //The code that goes after the table is there
           include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
           echo prm_Event::lights();
       }
    }
    //used if no method is found for a cloumn
    function column_default($item, $column_name){
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
            switch($column_name){
                /*case 'date_from':
                case 'date_to':
                    return '<span '. $green .'>'.$item->$column_name.'</span>';*/
                default:
                    return print_r($item,true); //Show the whole array for troubleshooting purposes
            }
        }
    //each column needs a method to render it
    function column_date_from($item){
        if(!is_null($item->date_from)){
            $green = ($this->updated_ID == $item->ID)? $this->green : '';
            $date = date_create($item->date_from);
            $date = date_format($date, PRM_DATE_FORMAT);
        }else $date = 'not set';
        return '<span '. $green .'>'.$date.'</span>';
    }
    function column_date_to($item){
        if(!is_null($item->date_to)){
            $green = ($this->updated_ID == $item->ID)? $this->green : '';
            $date = date_create($item->date_to);
            $date = date_format($date, PRM_DATE_FORMAT);
        }else $date = 'not set';
        return '<span '. $green .'>'.$date.'</span>';
    }
    function column_races($item){ //return the # of races for this event
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
        $prm_event = new prm_Event($item->ID);
        $races = $prm_event->number('races');
        return '<span '. $green .'>'.$races .'</span>';
    }
    function column_racers($item){
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
        $prm_event = new prm_Event($item->ID);
        $racers = $prm_event->number('racers').' / '.$prm_event->sum('races','max_entries');
        return $racers;
        return '<span '. $green .'>'.$racers .'</span>';
    }
    function column_status($item){
        include_once(PRM_PUGIN_PATH.'classes/prm-events.php');
        $prm_event = new prm_Event($item->ID);
        $light = $prm_event->status();
        return $light;
    }
    function column_name($item){
        $green = ($this->updated_ID == $item->ID)? $this->green : '';
        echo '<style type="text/css">';
        echo '.wp-list-table .column-name { width: 30%; }';
        echo '</style>';
            //Build row actions
        switch($item->status){
            case '0': // getting ready to open entries
                $actions = array(
                    'Open-Entries'    => sprintf('<a href="?page=%s&m=c&o=events&a=%s&i=%s&f=5m">Open Entries</a>',$_REQUEST['page'],'open',$item->ID),
                    'Races'    => sprintf('<a href="?page=%s&m=v&o=races&a=%s&e=%s&f=5m">Race Central</a>',$_REQUEST['page'],'list',$item->ID),
                    'View'    => sprintf('<a href="?page=%s&m=v&o=events&a=%s&i=%s&f=5m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case '3': //entries open
                $actions = array(
                    'Races'    => sprintf('<a href="?page=%s&m=v&o=races&a=%s&e=%s&f=5m">Race Central</a>',$_REQUEST['page'],'list',$item->ID),
                    'Close-Entries'    => sprintf('<a href="?page=%s&m=c&o=events&a=%s&i=%s&f=5m">Close Entries</a>',$_REQUEST['page'],'close-entries',$item->ID),
                    'View'    => sprintf('<a href="?page=%s&m=v&o=events&a=%s&i=%s&f=5m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case '5': // entries closed or sold out
                $actions = array(
                    'Close-Event'    => sprintf('<a href="?page=%s&m=c&o=events&a=%s&i=%s&f=5m">Close Event</a>',$_REQUEST['page'],'close-event',$item->ID),
                    'Open-Entries'    => sprintf('<a href="?page=%s&m=c&o=events&a=%s&i=%s&f=5m">Open Entries</a>',$_REQUEST['page'],'open',$item->ID),
                    'Races'    => sprintf('<a href="?page=%s&m=v&o=races&a=%s&e=%s&f=5m">Race Central</a>',$_REQUEST['page'],'list',$item->ID),
                    'View'    => sprintf('<a href="?page=%s&m=v&o=events&a=%s&i=%s&f=5m">View</a>',$_REQUEST['page'],'view',$item->ID)
                );
                break;
            case '7': // event is closed
                $actions = array(
                    'View'    => sprintf('<a href="?page=%s&m=v&o=events&a=%s&i=%s&f=5m">View</a>',$_REQUEST['page'],'view',$item->ID),
                    'Races'    => sprintf('<a href="?page=%s&m=v&o=races&a=%s&e=%s&f=5m">Race Central</a>',$_REQUEST['page'],'list',$item->ID),
                    'Archive'   => sprintf('<a href="?page=%s&action=%s&event=%s">Archive</a>',$_REQUEST['page'],'archive',$item->ID)
                );
                break;
        }

            //Return the title contents
            return sprintf('%1$s %2$s',
                /*$1%s*/ '<span '. $green .'>'.$item->name.' (id:'. $item->ID .')</span>',
                /*$2%s*/ $this->row_actions($actions)
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
                'name'     => 'Event Name',
                'status'    => 'Status',
                'date_from'  => 'From',
                'date_to'    => 'To',
                'races' => 'Races',
                'racers' => 'Entries'
            );
            return $columns;
        }
    // set which cloumns are sortable
    function get_sortable_columns() {
            $sortable_columns = array(
                'name'     => array('name',false),     //true means it's already sorted
                'status'    => array('status',false),
                'date_from'  => array('date_from',false),
                'date_to'  => array('date_to',false)
            );
            return $sortable_columns;
        }
    //Prepare the table with different parameters, pagination, columns and table elements
    function prepare_items() {
       global $wpdb;
       //$screen = get_current_screen();

       /* -- Preparing your query -- */
       $table_name = $wpdb->prefix.'prm_events';
       $sql = 'SELECT ID, name , status , date_from , date_to FROM '.$table_name.'
               where status < 9';

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