<?php
/**
 * see office-doc for rules
 */
class prm_Pack{
    var $license;
    var $lic_date;
    var $lic_domain;
    var $pack; //license member name
    var $lic_pack_posi;
    var $lic_sn; //serial number
    var $lic_client;
    var $max_comps;
    var $name; // the license display name
    function __construct($key = NULL){
        //read license key
        global $wpdb;
        if(is_null($key)){
            $sql = 'select license_sys from '.$wpdb->prefix.'prm_settings where ID = '. PRM_ORG_ID;
            $row = $wpdb->get_row($sql);
            $this->license = $row->license_sys;
        }else{ $this->license = $key; }
        if(is_null($this->license)){
            $this->set_jackie();
        }
        else{
            //get client#
            $this->lic_client = strstr($this->license,'%');
            $license = substr($this->license, 0,strrpos($this->license, '%'));
            $this->lic_client = ltrim($this->lic_client,'%'); //remove delimiter from client#
            // get SN
            $this->lic_sn = strstr($license,'!');
            $license = substr($license, 0,strrpos($license, '!'));
            $this->lic_sn = ltrim($this->lic_sn,'!');
            //split into SN and pack_posi
            $this->lic_pack_posi = substr($this->lic_sn,0,1);
            $this->lic_sn = substr($this->lic_sn,1);
            // get model
            $this->pack = strstr($license,'@');
            $license = substr($license, 0,strrpos($license, '@'));
            $this->pack = ltrim($this->pack,'@');
            // get domain
            $this->lic_domain = strstr($license,'|');
            $license = substr($license, 0,strrpos($license, '|'));
            $this->lic_domain = ltrim($this->lic_domain,'|');
            // get date
            $this->lic_date = rtrim($license,'|');
            //set license display name
            switch($this->pack){
                case 'oscar':
                    $this->name = 'Clubs';
                break;
                case 'ruby':
                    $this->name = 'Pro';
                break;
                case 'jack':
                    $this->name = 'Commercial';
                break;
            }
        }
        if(!$this->validate_license()){ //drop to jackie if invlaid
            $this->set_jackie();
        }
    }

    /*
     * display a form for user to install a new license
     */
    public static function new_license_form($page){
        echo '<h2>Installation of a New License Key</h2>';
        echo 'Enter the new license as you have received it below. Copy and paste usually works best.<BR>';
        echo '<form action="?page='. $page .'&m=m&o=pack&a=install&i='. PRM_ORG_ID .'&f=pc" method="post">';
        echo '<p><label for="license">New License Key: <input size="60" type="text" name="license" ></label><BR>
        When you click save below the license key will be validated and if valid installed and your new license will be available.</p>';
        echo '<p><input type="image" src="'. PRM_PLUGIN_URL .'images/save.jpg" alt="Update License"></p>';
        echo '</form>';
    }

    /*
     * used when a new license is being installed and returns true or false
     */
    function validate_license(){
        if($this->lic_client < 100) return false;
        if($this->lic_sn <= 100) return false;
        if(!$this->check_domain()) return false;
        $date = strtotime($this->lic_date);
        if($date < strtotime('2014-09-22'))return false;
        switch($this->lic_pack_posi){
            case 1:
                if($this->pack != 'jackie') return false;
            break;
            case 3:
                if($this->pack != 'oscar') return false;
            break;
            case 5:
                if($this->pack != 'ruby') return false;
            break;
            case 7:
                if($this->pack != 'jack') return false;
            break;
        }
        //if we get here all is good
        return true;
    }
    /*
     * if a new license is valid it's installed:
     * -> copy model_name to settings > last_model_sys
     * -> copy license settings > license_sys
     */
    function install(){
        echo '<span style="color: red">Vaidating license key</span><BR>';
        if($this->validate_license()){
            global $wpdb;
            //install new license key
            $sql = 'update '.$wpdb->prefix.'prm_settings set license_sys = "'.$this->license.'"
            where ID ='. PRM_ORG_ID;
            $result = $wpdb->query($sql);
            if($result){
                echo '<BR><BR><span style="color: green"><strong>Congratulations! Your new license key has been installed</strong></span>';
                echo '<BR><BR>Your new license level is now: <strong>'.$this->name.'</strong>';
            }
        }else{
            //key is not valid
            echo '<span style="color: red">Sorry it seems that this key is not valid</span><BR>
            if you purchased a new license key please be sure to copy and paste it. If the problem persists please contact us so we can fix this for you ASAP.';
        }
    }
    /*
     * if license domain != current domain, display message a top of every screen
     */
    function check_domain(){
               //implement before releasing pro
        //challenge is to ensure it matches even if www is set until implemented return true
        return true;
    }
    public static function get_comps(){
        global $wpdb;
        $sql = 'select count(*) as comps from '.$wpdb->prefix.'prm_competitors';
        $row = $wpdb->get_row($sql);
        $comps = $row->comps;
        return $comps;
    }
    function get_member(){
        return $this->pack;
    }
    function get_max_comps(){
        return $this->max_comps;
    }
    function get_name(){
        return $this->name;
    }
    function get_posi(){
        return $this->lic_pack_posi;
    }
    function set_jackie(){  //set to jackie
        $this->pack = 'jackie';
        $this->max_comps = 25;
        $this->name = 'Basic';
    }
    function test(){ //display key vars
        echo 'raw license: '.$this->license.'<BR>';
        echo 'date: '.$this->lic_date .'<BR>';
        echo 'domain: '.$this->lic_domain .'<BR>';
        echo 'pack (mem name): '.$this->pack .'<BR>';
        echo 'position: '.$this->lic_pack_posi .'<BR>';
        echo 'SN: '.$this->lic_sn .'<BR>';
        echo 'client#: '.$this->lic_client .'<BR>';
        echo 'max comps: '.$this->max_comps .'<BR>';
        echo 'name: '.$this->name .'<BR>';
    }
}
