<?php
/**
 * prm admin controls
 */

class prm_ADMIN{
    function __construct(){
        add_action('admin_menu', array(&$this, 'prm_admin_menu'));
    }

    function prm_admin_menu()
    {	//create a main admin panel
		//create a sub admin panel link above
        add_menu_page('prm', 'Pedal On RM', 'administrator', 'prm', array(&$this,'overview'));
        add_submenu_page('prm', 'Crew', 'Crew', 'administrator', 'prm-crew', array(&$this,'crew'));
        add_submenu_page('prm', 'Series', 'Series', 'administrator', 'prm-series', array(&$this,'series'));
        add_submenu_page('prm', 'Courses', 'Courses', 'administrator', 'prm-courses', array(&$this,'courses'));
        add_submenu_page('prm', 'Events', 'Events', 'administrator', 'prm-events', array(&$this,'events'));
        add_submenu_page('prm', 'Competitors', 'Competitors', 'administrator', 'prm-competitors', array(&$this,'competitors'));
        if(PRM_PACK_POSI > 4) add_submenu_page('prm', 'Perm Bibs', 'Perm Bibs', 'administrator', 'prm-bibs', array(&$this,'bibs'));
        add_submenu_page('prm', 'Settings', 'Settings', 'administrator', 'prm-settings', array(&$this,'settings'));
        //add_submenu_page('rpm', 'POs', 'POs', 'administrator', 'avb-pos', array(&$this,'pos'));
        //add_submenu_page('rpm', 'Items', 'Items', 'administrator', 'avb-items', array(&$this,'items'));
    }
    public function overview(){
        echo '<h2>Pedal On Race Management (PRM) Overview</h2>';
        ?>
    <h1>Overview and Quick Start</h1>
    Note that when displaying a list of items the last updated item will be in green writing and where statuses are being used the status lights are displayed at the end of the list.

    Generally the status lights have a similar meaning to traffic lights:
    <ul>
    	<li>Orange – getting ready</li>
    	<li>Green – racing or open</li>
    	<li>Red – finished or closed</li>
    	<li>Grey – closed</li>
    </ul>
    All text shown as [button] has the meaning of please click this button or menu item.

    It can become very confusing about who we're referring to at times, so I use these terms, mostly to ensure I don't confuse myself:
    <ul>
    	<li>PRM – short for Pedal On Race Management</li>
    	<li>Series – This is the link between repeating events such as a Marathon that runs every year.</li>
    	<li>Event – the actual party, an event has one or more races.</li>
    	<li>Course – the physical location a race runs on.</li>
    	<li>On Course – a list of all competitors that are racing and have not yet finished</li>
    	<li>Competitor – a person in our list of people who has entered an active or past event</li>
    	<li>Race – the actual race competitors enter into such as the “Ultra Female Race”. Each event usually has several races. For a marathon you might have Male and Female races with separate awards, however several races can run concurrently, eg the men and women in the marathon could start at the same time.</li>
    	<li>Entry – this is a competitors entering into a race. The Entries function will give you a list of all competitors that have entered a race.</li>
    	<li>Registration – is when a competitor arrives at the event venue and receives their race kit including their bib from the organisers</li>
    	<li>Sign-in – occurs when a registered competitor arrives at the start line just before the race start and has been “ticked off” as ready to race</li>
    	<li>Live Results – A list you can show on your website that shows the progress of all racers from sign-in to finish for all to see</li>
    	<li>Results – A list you can show on your website that shows the official results with ranking for each race after you have reviewed and made any changes if required.</li>
    	<li>Simple Race Timing – After starting a race in PRM, choose [On Course] and click [Finish] as each competitor finishes. This will ensure you know the status of each racer and their finish time.</li>
    </ul>
    <h1 class="western">Running an Event</h1>
    The minimum sequence of tasks to run a club event using PRM Basic or Clubs version. I'm assuming Simple race timing is being used:
    <ol>
    	<li>Create a series an than an Event</li>
    	<li>Create at least one Course</li>
    	<li>Create at least one crew member as your race director, however you can also enter all of your marshals as crew members</li>
    	<li>Create a race for each category of racers such as Ultra Female Race, Ultra Masters ...</li>
    	<li>Enter competitors into their respective races</li>
    	<li>Register racers and issue with bib, for very small races you could simply use their name</li>
    	<li>At the start line sign racers into the race, live results will now appear on your website providing you have inserted the shortcode on your website.</li>
    	<li>Start a race(s)</li>
    	<li>Click [Finish] as each racer finishes</li>
    	<li>Process results and publish on your website</li>
    </ol>
    <h1 class="western">Show Results on your Website</h1>
    Results are published directly on your website for all visitors to your site to see, read on to see how this is done.

    We have two type of results, Live Results and Published Results. Live results is a list of all competitors currently on course or have signed into their race and Published Results are the final race results after they have been reviewed and published by you. See below for more information on each. However before we can use these results we need to install the shortcodes. Shortcodes are little bits of “secret” code that you place in your website pages or posts to display live results that look like this:<BR>

    [prm_live_results events_id=”1”]<BR>

    or for official results you can list results by a race or a course like this:<BR>

    [prm_results events_id=”1” races_id=”20”] or<BR>

    [prm_results events_id=”1” courses_id=”3”]<BR>

    and when visitors open this page on your website the above code will be replaced with the results list.
    <h2 class="western">Installation</h2>
    OK, I need to ask you to be a bit of a Wordpress ninja now to make the display of results work in your website. This is to register the shortcodes we'll use to insert the result pages into your site.

    You need to log into the admin panel of your website and follow these steps:
    <ol>
    	<li>Click on Appearance &gt; Editor</li>
    	<li>On the left-hand pane find funtions.php (depending on your theme it will be listed as functions.php or some themes list it with a different name and (functions.php) in brackets.</li>
    	<li>After clicking functions.php, scroll right to the end and copy and paste this line:</li>
    </ol>
    include(WP_CONTENT_DIR . '/plugins/pedal-on-race-management/functions/prm-functions.php');<BR>

    and than click [Update File]<BR>

    You should now receive a message: File edited successfully. At the top of the screen.<BR>

    Now you're good to use the shortcodes in your website.<BR>
    <BR>
    The best idea is to download the complete <a href="http://pedalon.com.au/race-management/about-prm" >PRM manual from our website here</a> to get a better understanding on how to use PRM.

        <?php
    }
    public function events(){
        //call router to route request
        include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
        prm_Router::route();
    }
    public function series(){
        //call router to route request
        include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
        prm_Router::route();
    }
    public function crew(){
        //call router to route request
        include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
        prm_Router::route();
    }

    public function competitors(){
       //call router to route request
       include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
       prm_Router::route();
    }
    public function courses(){
       //call router to route request
       include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
       prm_Router::route();
    }
    public function bibs(){
       //call router to route request
       include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
       prm_Router::route();
    }
    public function settings(){
       //call router to route request
       include_once(PRM_PUGIN_PATH.'classes/prm-router.php');
       prm_Router::route();
    }
}
$prm_ADMIN = &new prm_ADMIN();//instance of the plugin class