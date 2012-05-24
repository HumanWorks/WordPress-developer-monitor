<?php
/*
Plugin Name: Developer monitor by VentureGeeks
Plugin URI: http://venturegeeks.com/blog/wp-developer-monitor/
Description: Check all the running queries and other development information in Firebug. Great to debug performance issues in live web sites.
Author: Nick Papanotas (@HumanWorks)
Author URI: http://venturegeeks.com/
Version: 1
*/
define('VG_INC_PATH', dirname(__FILE__));
include VG_INC_PATH . '/settings.php';


/* Check if the plugin is active */
function dev_active(){
	global $current_user;
	return in_array(get_option('group_id'), $current_user->roles);
}

function dev_init(){
	if ( !dev_active() ) return;
	define('SAVEQUERIES', true);
	global $wpdb;
	$wpdb->save_queries = true;//HyperDB
	$time = microtime();
	$time = explode(' ', $time);
	define('DEV_START', ( $time[1] + $time[0] ));
	//define('DEV_USE_WRAPPER', 1);//ToDo: This should be an option in WP use it when you have problem sending headers (eg. w/ nginx)
	ob_start();
	return;	
}

function dev_display(){
	if ( !dev_active() ) return;
	if ( defined('DEV_USE_WRAPPER') ) 
		include VG_INC_PATH . '/firePHP.wrapper.php';
	else if( !class_exists('FirePHP'))
		include VG_INC_PATH . '/FirePHPCore/FirePHP.class.php';
    global $wpdb;
    
    $time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$total_time_load = round(($time - DEV_START), 6);
    
    $expensive_query = "";
    $total_time = $total_queries = $expensive_query_time = 0;
 	
 	$q_selects [] = array("SQL", "Execution Time", "Calling Function");
    $q_inserts [] = array("SQL", "Execution Time", "Calling Function");
 	$q_updates [] = array("SQL", "Execution Time", "Calling Function");
 	$q_other [] = array("SQL", "Execution Time", "Calling Function");
 	
    foreach($wpdb->queries as $query) {
        $total_queries++;
        $query[0] = ltrim($query[0]); // Some queries come with spaces...

        /* Get the most expensive query */
        if($query[1] > $expensive_query_time) {
            $expensive_query_time = $query[1];
            $expensive_query = $query[0];
            $total_time += $query[1];
        }
        
        if(strtoupper(substr($query[0], 0, 6))=='SELECT'){
        	$q_selects [] = $query;
        	$q_selects_time += $query[1];
        } else if(substr($query[0], 0, 6)=='INSERT'){
        	$q_inserts [] = $query;
        	$q_inserts_time += $query[1];
        } else if(substr($query[0], 0, 6)=='UPDATE'){
        	$q_updates [] = $query;
        	$q_updates_time += $query[1];
        }else {
        	$q_other [] = $query;
        	$q_other_time += $query[1];
        }
    }
 
    $firephp = FirePHP::getInstance(true);
 
    /* Display the queries in a formatted table */
    $firephp->group('Development information', array( 'Collapsed' => false, 'Color' => '#000'));
    $firephp->log('Page load time: '.$total_time_load);
 	$firephp->group($total_queries . " queries took " . $total_time . " seconds.", array( 'Collapsed' => false, 'Color' => '#000'));
 	
 	if ( ($c=count($q_selects)-1) >0 ) $firephp->table($c.' SELECT queries took '.$q_selects_time. ' seconds' , $q_selects);
	if ( ($c=count($q_updates)-1) >0 ) $firephp->table($c.' UPDATE queries took '.$q_updates_time. ' seconds' , $q_updates);
 	if ( ($c=count($q_inserts)-1) >0 ) $firephp->table($c.' INSERT queries took '.$q_inserts_time. ' seconds' , $q_inserts);
 	if ( ($c=count($q_other)-1) >0 ) $firephp->table($c.' other queries took '.$q_other_time. ' seconds' , $q_other);
 	
    /* Display the query summary */
    $firephp->group('Query Summary', array( 'Collapsed' => false, 'Color' => '#000'));
    $firephp->log($total_queries, 'Total Queries');
    $firephp->log($expensive_query, 'Expensive Query');
    $firephp->log($expensive_query_time, 'Expensive Query Time');
    $firephp->groupEnd();
    $firephp->groupEnd();
    
    /* Environment stuff */
    $firephp->group('Environment', array( 'Collapsed' => false, 'Color' => '#000'));
    
    if ( !empty($_SESSION) ) {
    	$srv = array('Key', 'Value');
    	foreach( $_SESSION as $k => $v)
    		$srv [] = array( $k, $v);
	    $firephp->table('_SESSION',  $srv);
    }
    
    if ( !empty($_COOKIE) ) {
    	$srv = array('Key', 'Value');
    	foreach( $_COOKIE as $k => $v)
    		$srv [] = array( $k, $v);
	    $firephp->table('_COOKIE',  $srv);
    }
    
    if ( !empty($_POST) ) {
	    $srv = array('Key', 'Value');
    	foreach( $_POST as $k => $v)
    		$srv [] = array( $k, $v);
	    $firephp->table('_POST',  $srv);
    }
    
    if ( !empty($_GET) ) {
	    $srv = array('Key', 'Value');
    	foreach( $_GET as $k => $v)
    		$srv [] = array( $k, $v);
	    $firephp->table('_GET',  $srv);
    }
    
    global $wp_object_cache;
    if ( isset($wp_object_cache->stats) && count($wp_object_cache->stats) > 0 ){
    	$srv = array('Action', 'Total');
    	foreach( $wp_object_cache->stats as $k => $v)
    		$srv [] = array( $k, $v);
    	$firephp->table('WP_Object_Cache', $srv);
    }
    
    $srv = array('Key', 'Value');
    foreach( $_SERVER as $k => $v)
    	$srv [] = array( $k, $v);
    $firephp->table('_SERVER',  $srv);
    
    // Didn't had the time to test $_FILES, so please drop me a line if you see any prob (or if you find a better way to do this)
    if ( !empty($_FILES)){
    	$f = array();
    	foreach( $_FILES as $k => $v ){
    		$f = array( $k, var_export($v, true));
    	}
    	$firephp->table('_FILES',  f);
    }
    
    $firephp->groupEnd();
    ob_flush();
    if ( defined('DEV_USE_WRAPPER') ) 
    	echo '<div style="display: none;"><pre>'.$firephp->buffer.'</pre></div>';
	return;
}

/*
Add action hooks
*/

add_action('init', 'dev_init');
add_action('wp_footer', 'dev_display');
add_action('admin_init', 'dev_display');
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'dev_plugin_settings_link' );
