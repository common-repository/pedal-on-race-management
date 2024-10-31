<?php
/**
 *
 */
class prm_Help{
    //var $table_name;
    function __construct(){

    }
    /*public static function display_msg($class,$method,$cond=0){
        global $wpdb;
        $cond = ($cond == 0) ? '' : ' and condition = '.$cond;
        $table_name = $wpdb->prefix.'prm_help';
        $sql = 'select message from '.$table_name.' where class = "'.$class.'"
               and method = "'.$method.'"'.$cond;
        $row = $wpdb->get_row($sql);
        $output = $row->message;
        return $output;
    }*/
    public static function get_msg($msg_ID,$position=NULL){ // return msg by msg ID
        global $wpdb;
        $position = (!is_null($position)) ? ' and position = "'.$position.'"' : '';
        $sql = 'select * from '.$wpdb->prefix.'prm_help where ID = '. $msg_ID.
        $position;
        $row = $wpdb->get_row($sql);
        // format message as per type
        switch($row->type){
            case 'error':
                $output = '<BR><span style="color: red">'. $row->message .'</span> ';
                break;
            case 'success':
                $output = '<BR><span style="color: green">'. $row->message .'</span> ';
                break;
            default:
                $output = '<BR>'.$row->message;
        }

        return $output;

    }
}
