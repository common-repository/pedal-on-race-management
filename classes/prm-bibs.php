<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wolfskafte-zauss
 * Date: 11/09/14
 * Time: 2:10 AM
 * To change this template use File | Settings | File Templates.
 */
class prm_Bibs{
    public static function next_bib(){
        //insert removal of perm bib if end date is reached
        global $wpdb;
        $sql = 'select bib from '.$wpdb->prefix.'prm_bibs';
    }
    /*
     * need to check if bib has been assign for current event and
     * if its in the permanent bibs table
     * if all good return NULL else 1
     */
    public static function unique($bib, $event_ID){
        global $wpdb;
        //check if bib has been assigned in this event as yet if bib !empty
        $sql = 'select bib_number from '. $wpdb->prefix.'prm_racers
            where events_ID = '. $event_ID.' and bib_number = '.$bib;
        $result = $wpdb->get_row($sql);

        if(is_null($result)){ // not assigned to racer, lets check if in perm bibs
            $sql = 'select bib_number from '.$wpdb->prefix.'prm_permanent_bibs where '.
                'bib = '.$bib;
            $result = $wpdb->get_row($sql);
            if(is_null($result)){ return NULL; }else{ return 1; }
        }else{ // result !null return
            return 1;
        }

    }

}
