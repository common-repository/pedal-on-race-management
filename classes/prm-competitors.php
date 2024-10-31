<?php
/**
 *
 */
/*class prm_Competitor{
    var $c_ID;
    var $table_name;
    var $competitor;
    function __construct($c_ID){
        global $wpdb;
        $this->c_ID = $c_ID;
        $this->table_name = $wpdb->prefix.'prm_competitors';
        $sql = 'select * from '.$this->table_name.' where ID = '.$c_ID;
        $this->competitor = $wpdb->get_row($sql);
    }
    public static function add(){
        echo '<h2>Add Competitor</h2>';
        include_once(PRM_PUGIN_PATH.'classes/prm-forms.php');
        $fields = array();
        $fields['first_name'] = 'First Name*: ';
        $fields['last_name'] = 'Last Name: ';
        $fields['birth_date'] = 'Birth Date*(yyyy-mm-dd): ';
        $fields['email'] = 'Email*: ';
        $fields['s_gender'] = 'Gender*: ';
        $fields['street'] = 'Street: ';
        $fields['town'] = 'Town / Suburb: ';
        $fields['s_state'] = 'State: ';
        $fields['post_code'] = 'Post Code: ';
        $fields['t_notes'] = 'Notes: ';
        echo prm_Forms::add('prm_competitors',$fields);
    }
    public function get_name(){
        $name = $this->competitor->first_name.' '.$this->competitor->last_name;
        return $name;
    }

   /* public static function view(){
            echo '<h2>Competitor Details</h2>';
            include_once(PRM_PUGIN_PATH.'classes/prm-forms.php');
            $fields = array();
            $fields['status'] = 'Status: ';
            $fields['first_name'] = 'First Name: ';
            $fields['last_name'] = 'Last Name: ';
            $fields['dob'] = 'DOB: ';
            $fields['email'] = 'email: ';
            $fields['sex'] = 'Sex: ';
            $fields['street'] = 'Street: ';
            $fields['town'] = 'Town / Suburb: ';
            $fields['state'] = 'State: ';
            $fields['post_code'] = 'Post Code: ';
            $fields['permanent_bib'] = 'Permanent bib: ';
            $fields['t_notes'] = 'Notes: ';
            $fields['edit_action'] = '';
            echo prm_Forms::view('prm_competitors',$fields);



        }*//*
}*/
?>
