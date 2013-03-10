<?php
	if (!function_exists('glossy_adminMenu'))
	{
		function glossy_adminMenu()
		{
			$glossy = Glossy::Instance();

			$accessLevel = get_option('gs_access', 'admin');
			$accessString = 'install_plugins';

			switch ($accessLevel) {
				case 'contributor':
					$accessString = 'edit_posts';
					break;

				case 'editor':
					$accessString = 'edit_others_posts';
					break;

				default:
					$accessString = 'install_plugins';
					break;
			}

			add_menu_page('Manage Entries', 'Glossy', $accessString, 'glossy', 'gs_manageEntries');
			$page = add_submenu_page('glossy', 'Manage Entries', 'Manage Entries', $accessString, 'manage-entries', 'gs_manageEntries');
			add_submenu_page('glossy', 'Add Entry', 'Add Entry', $accessString, 'glossy-add-entry', 'gs_addEntry');
			add_submenu_page('glossy', 'Glossy Options', 'Glossy Options', $accessString, 'glossy-settings', 'gs_options');
			add_submenu_page('glossy', 'Export Entries', 'Export Entries', $accessString, 'glossy-export', 'gs_export');
			add_submenu_page('glossy', 'Import Entries', 'Import Entries', $accessString, 'glossy-import', 'gs_import');

			remove_submenu_page('glossy', 'glossy');

			add_action('admin_print_styles-' . $page, array($glossy, 'initPlugin'));
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
	
	if (!function_exists('gs_options')) {
		function gs_options()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.options.php');
			
			gs_options_panel();
		}
	}

	if (!function_exists('gs_addEntry')) {
		function gs_addEntry()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.addEntry.php');
			
			gs_addEntry_panel();
		}
	}
	
	if (!function_exists('gs_manageEntries')) {
		function gs_manageEntries()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.manageEntries.php');
			
			gs_manageEntries_panel();
		}
	}

	if (!function_exists('gs_export')) {
		function gs_export()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.export.php');
			
			gs_export_panel();
		}
	}

	if (!function_exists('gs_import')) {
		function gs_import()
		{
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.import.php');
			
			gs_import_panel();
		}
	}
	
	add_action('admin_menu', 'glossy_adminMenu');
	add_filter('wp_head', 'gs_addEntry_editor');
?>