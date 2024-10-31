<?php
/**
 *
 */
class prm_Crew{
    var $c_ID;
    var $table_name;
    var $member;
    function __construct($c_ID){
        global $wpdb;
        $this->c_ID = $c_ID;
        $this->table_name = $wpdb->prefix.'prm_crew';
        $sql = 'select * from '.$this->table_name.' where ID = '.$c_ID;
        $this->member = $wpdb->get_row($sql);
    }

}
