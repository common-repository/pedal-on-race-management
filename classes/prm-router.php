<?php
/**
 * This gets the query string and directs request to relevant class
 * roughly built on MVC pattern
 * querystring format: ?page=xxx&m=x&o=x&a=x&i=x&e=x&f=x
 *   m(vc) = m,v,c //where c is the object class
 *   o = the object or table eg racers (always plural) without prefix
 *   a = method or action
 *   i = only set if action requires an ID to be passed
 *   e = events_ID
 *   f = class code and method code from the calling object
 *   msg = optional, if set needs to be ID of msg to be displayed
 *   if object = racers, where and type are passed along to build options dynamiclly, however these are not tested as part of debug
 *
 * See the from method to see codes for classes and methods
 */
class prm_Router{
    public static function route(){ //route request tp appropriate class
        $debug = 2; // set to 2 to run in debug mode
        // if debug switch is set die and return from wher the request was made
        if($_GET['d'] == 2) die(self::from($_GET['f']));

        if(!empty($_GET['msg'])){ //display message first
            include_once(PRM_PUGIN_PATH.'classes/prm-help.php');
            echo prm_Help::get_msg($_GET['msg']);
        }
        $page = $_GET['page'];
        include_once(PRM_PUGIN_PATH.'classes/prm-views.php');
        switch($_SERVER['QUERY_STRING']){ // check if call is from main menu, hence only page will be set
            case 'page=prm-events':
            case 'page=prm-crew':
            case 'page=prm-series':
            case 'page=prm-competitors':
            case 'page=prm-courses':
            case 'page=prm-bibs':
                $object = ltrim($page,'prm-');
                prm_View::object_list($object);
                exit;
            break;
            case 'page=prm-settings':
                $object = ltrim($page,'prm-');
                prm_View::view($page,$object,PRM_ORG_ID,NULL,9);
                exit;
            break;

        }

        // if debug mode is set check all vars and report if there's a problem
        $error = self::debug_report($debug);
        if(!is_null($error)) echo $error;
        $object = $_GET['o']; //object name = table name and is always plural
        $method = $_GET['a']; // the method or action to perform
        $event_ID = (!empty($_GET['e']))? $_GET['e'] : NULL;
        $object_id = (!empty($_GET['i']))? $_GET['i'] : NULL;
        $mvc = $_GET['m']; // MVC - model, view or controller (class) call

        switch($mvc){
            case 'm': // model, DB call
                $class_file = 'prm-model.php';
                $class_name = 'prm_Model';
            break;
            case 'v': // view
                $class_file = 'prm-views.php';
                $class_name = 'prm_View';
            break;
            case 'c': // this is the class for this object
                $class_file = $object;
                $class_file = 'prm-'.$class_file .'.php'; //add prefix
                $class_name = 'prm_'.ucfirst(rtrim($object,'s'));

            break;
            default:
                $class_file = 'm = not found';
        }
        include_once(PRM_PUGIN_PATH.'classes/'.$class_file);
        // route request
           // I haven't been able to figure out how to do: $class::$method
        switch($method){
            case 'add': // we need to set events_ID and races_ID for racers
                if($object == 'racers'){
                    $races_ID = (!empty($_GET['races_ID']))? $_GET['races_ID'] : NULL;
                    if(is_null($races_ID)){ // need to use object_id as races_ID if set
                        $races_ID = (!is_null($object_id))? $object_id : NULL;
                    }
                    $class_name::add($page,$object,$event_ID,$races_ID);
                }else{
                    $class_name::add($page,$object,$event_ID);
                }
            break;
            case 'insert': //static call
                $class_name::insert($page,$object,$_POST,$event_ID);
            break;
            case 'license':
                $class_name::new_license_form($page);
                break;
            case 'view':
                //static call
                $class_name::view($page,$object,$object_id,$event_ID,NULL,$_GET['f']);
            break;
            //the following are dynamic calls, id check is at the top
            case 'edit':
                $my_object = new prm_Model();
                if($page == 'prm-events'){
                    $my_object->edit($page,$object,$object_id,$event_ID);
                }else{ $my_object->edit($page,$object,$object_id); }
            break;
            case 'update':
                $my_object = new prm_Model();
                if($page == 'prm-events'){
                    $my_object->update($page,$object,$object_id,$_POST);
                }else{ $my_object->update($page,$object,$object_id,$_POST); }
            break;
            case 'del':
                $my_object = new prm_Model();
                $my_object->del($object,$object_id,$event_ID);
            break;
            case 'install':
                $my_object = new prm_Pack($_POST['license']);
                $my_object->install();
                break;
            case 'list':
                if($object == 'racers'){ // we need to make special settings to list racers
                    prm_Racer::list_items($object_id,$_GET['where'],$_GET['type'],$event_ID);
                }else{
                    //$my_object = new prm_View();
                    $class_name::object_list($object,$object_id,NULL,$event_ID);
                }
                break;
            case 'FIN':
                $my_object = new prm_Racer($object_id);
                $my_object->FIN();
                break;
            case 'DNF':
                $my_object = new prm_Racer($object_id);
                $my_object->DNF();
                break;
            case 'DQ':
                $my_object = new prm_Racer($object_id);
                $my_object->DQ();
                break;
            case 'results':
                $my_object = new prm_Races($object_id);
                $my_object->results();
                break;
            case 'publish':
                $my_object = new prm_Races($object_id);
                $my_object->publish();
                break;
            case 'placings':
                $my_object = new prm_Races($object_id);
                $my_object->placings();
                break;
            case 'rego':
                $my_object = new prm_Racer($object_id);
                $my_object->rego();
                break;
            case 'sign-in':
                $my_object = new prm_Racer($object_id);
                $my_object->sign_in();
                break;
            case 'start':
                $my_object = new prm_Races($object_id);
                $my_object->start();
                break;
            case 'open':
                $my_object = new prm_Event($object_id);
                $my_object->open_entries();
                break;
            case 'close-entries':
                $my_object = new prm_Event($object_id);
                $my_object->close_entries();
                break;
            case 'close-event':
                $my_object = new prm_Event($object_id);
                $my_object->close_event();
                break;
            default:
                if(PRM_DEBUG == 'Yes')
                    die('method does not exist');
        }
    }
    private static function debug_report($debug){

        //lets get all the vars
        if($debug == 2){
            $error = NULL;
            if(empty($_GET['o'])) $error = 'object not set<BR>';
            if(empty($_GET['a'])) $error .= 'action not set<BR>';
            //no event_ID is only allowed if a new event is being added or if the page != prm-events
            // or o=racers
            if($_GET['page'] == 'prm-events'){
                if(empty($_GET['e']) && $_GET['o'] != 'events' && $_GET['o'] != 'racers' ) $error .= 'event_ID not set<BR>';
            }
            if(empty($_GET['m'])) $error .= 'mvc not set<BR>';
            if(empty($_GET['f'])) $error .= 'from not set<BR>';
            if(empty($_GET['page'])) $error .= 'page not set<BR>';
            switch($_GET['a']){
                case 'add':
                case 'insert':
                case 'list':
                    // do nothing since id is not needed
                break;
                default:
                    if(empty($_GET['i'])) $error .= 'object_id is not set<BR>';
            }
            $from = self::from($_GET['f']);
            if(!is_null($error)){
                $error = '<<<<<<<<<<<<  ERROR >>>>>>>>>>><BR>'.$error;
                $error .= $from.'<BR>';
                if(!empty($_GET['msg']))$error .= 'msg ID = '.$_GET['msg'].'<BR>';
            }
        }
        return $error;
    }
    private static function from($from){ // decode from where the request was made and return
        $pos_1 = substr($from,0,1); //position one is the class
        switch($pos_1){
         case '1':
            $class = 'list-competitors-not-entered';
         break;
         case '2':
             $class = 'list-competitors';
         break;
         case '3':
             $class = 'list results';
         break;
         case '4':
             $class = 'list-courses';
         break;
         case '5':
             $class = 'list-events';
         break;
         case '6':
             $class = 'list-races';
         break;
         case '7':
             $class = 'list racers';
         break;
         case '8':
             $class = 'list series';
         break;
         case '9':
             $class = 'list crew';
         break;
         case '0':
             $class = 'list xxxx';
         break;
         case 'a':
             $class = 'admin';
         break;
         case 'b':
             $class = 'bibs';
         break;
         case 'c':
             $class = 'competitors';
         break;
         case 'd':
             $class = 'crew';
         break;
         case 'e':
             $class = 'events';
         break;
         case 'f':
             $class = 'courses';
             break;
         case 'g':
            $class = 'racers';
            break;
         case 'h':
             $class = 'help';
         break;
         case 'm': //(DB)
             $class = 'model ';
         break;
         case 'o':
             $class = 'options';
         break;
         case 'p':
                $class= 'pack';
                break;
         case 'r':
             $class = 'racers';
         break;
         case 'v':
             $class = 'views';
         break;
            default:
                $class = 'not found';
         
        }
        // now find method
        $pos_2 = substr($from,1,1);
        switch($pos_2){       
            case 'a':
                $method = 'add';
            break;
            case 'c':
                $method = 'license';
                break;
            case 'd':
            $method = 'delete';
            break;
            case 'e':
                $method = 'edit';
            break;
            case 'i':
                $method = 'insert';
            break;
            case 'l':
                $method = 'list-header';
            break;
            case 'm':
                $method = 'list-col_name';
            break;
            case 'o':
                $method = 'on_course';
                break;
            case 'p':
                $method = 'display_hd';
                break;
            case 'r':
                $method = 'archieve';
            break;
            case 't':
                $method = 'table_options';
                break;
            case 'u':
                $method = 'update';
            break;
            case 'v':
                $method = 'view';
            break;
            default:
                $method = 'not found';
        }
        return 'Calling class: '.$class.' and method: '.$method;
    }
}
