<?php
	
	// if uninstall.php is not called by WordPress, die
	if(!defined('WP_UNINSTALL_PLUGIN')){
		die;
	}
	
	delete_option('reconews_db_version');
	delete_option('reconews_title');
	delete_option('reconews_update_frequency');
	delete_option('reconews_view_count');
	
	// for site options in Multisite
	delete_site_option($option_name);
	
	// drop a custom database table
	global $wpdb;
	$table_name = $wpdb->prefix.'reconews';
	$wpdb->query("DROP TABLE IF EXISTS ".$table_name.";");
	
?>