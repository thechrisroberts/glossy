<?php
	if (!function_exists('glossy_adminMenu'))
	{
		function glossy_adminMenu()
		{
			add_menu_page('Manage Entries', 'Glossy', 'install_plugins', 'glossy-settings', 'gs_manageEntries');
			add_submenu_page('glossy-settings', 'Add Entry', 'Add Entry', 'install_plugins', 'glossy-add-entry', 'gs_addEntry');
		}
	}
	
	if (!function_exists('gs_addEntry_editor')) {
		function gs_addEntry_editor()
		{
			wp_admin_css('thickbox');
			wp_enqueue_script('post');
			wp_enqueue_script('media-upload');
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('tiny_mce');
			wp_enqueue_script('editor');
			wp_enqueue_script('editor-functions');
			add_thickbox();
		}
	}
	
	if (!function_exists('gs_addEntry'))
	{
		function gs_addEntry()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.addEntry.php');
			
			gs_addEntry_panel();
		}
	}
	
	if (!function_exists('gs_manageEntries'))
	{
		function gs_manageEntries()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.manageEntries.php');
			
			gs_manageEntries_panel();
		}
	}
	
	add_action('admin_menu', 'glossy_adminMenu');
	add_filter('wp_head', 'gs_addEntry_editor');
?>