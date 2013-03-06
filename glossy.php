<?php
/*
Plugin Name: Glossy
Plugin URI: http://croberts.me/glossy/
Makes it easy to create site-wide glossary or dictionary entries which pop up using the Tippy plugin
Version: 1.5.7
Author: Chris Roberts
Author URI: http://croberts.me/
*/

/*  Copyright 2013 Chris Roberts (email : chris@dailycross.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (!function_exists('gs_tippy_check')) {
	function gs_tippy_check()
	{
		// Check for the presence of Tippy
		if (!function_exists('tippy_getLink'))
		{
			echo '<div id="message" class="error">The Tippy plugin appears to be missing but is required by the Glossy plugin.</div>';
		}
	}
}

if (!function_exists('gs_scanContent')) {
	function gs_scanContent($content)
	{
		preg_match_all('/\[(?:gs|glossy(?!i))([^\]]+)?\](?:([^\[]+)\[\/(?:gs|glossy)\])?/', $content, $glossyMatches);
		
		for ($i = 0 ; $i < sizeof($glossyMatches[0]) ; $i++)
		{
			$glossySet = $glossyMatches[0][$i];
			$options = $glossyMatches[1][$i];
			$headertext = $glossyMatches[2][$i];
			preg_match_all('/\s([^\s]*)?/', $options, $splitOptions);

			// echo '<pre>';
			// echo $glossySet ."<br />";
			// print_r($splitOptions);
			// echo '</pre>';

			$gs_term = '';
			$gs_text = $headertext;
			$gs_inline = '';

			foreach ($splitOptions[0] as $optionPair) {
				// print_r($optionPair);
				preg_match('/term=["\']([^"]+)["\']/', $optionPair, $terms);
				if (!empty($terms)) {
					$gs_term = $terms[1];
				} else {
					preg_match('/\s([^\s\"\'\/\]]*)?/', $optionPair, $termMatch);

					if ($termMatch[0] != "inline=") {
						$gs_term = $termMatch[0];
					}
				}

				preg_match('/inline=["\']([^"]+)["\']/', $optionPair, $inlines);
				if (!empty($inlines)) {
					$gs_inline = $inlines[1];
				}
			}
			// echo "Term: ". $gs_term ."<br />";
			$gs_tippy = gs_display(trim($gs_term), trim($gs_text), $gs_inline);
			$content = preg_replace('/'. preg_quote($glossySet, '/') .'/', $gs_tippy, $content, 1);
		}

		// die();
		return $content;
	}
}

// Display the glossy entries on an index page
if (!function_exists('gs_indexShortcode')) {
	function gs_indexShortcode($atts)
	{
		$showingIndex = true;
		
		extract(shortcode_atts(array(
			'header' => 'on',
			'inline' => 'false'
		), $atts));
		
		$gs_indexList = gs_get_names('alpha');
		$gs_outputList = "";
		
		// Display the header of first characters
		if ($header == "on") {
			$gs_outputList .= '<div id="gs_index"><a name="gs_index"></a><span id="gs_indexTitle">Index:</span>';
			
			foreach ($gs_indexList as $gs_indexAbbrev => $gs_indexItems) {
				$gs_outputList .= '<a class="gs_indexAbbrev" href="#gs_indexAbbrevList_'. $gs_indexAbbrev .'">'. $gs_indexAbbrev .'</a>';
			}
			
			$gs_outputList .= '</div>';
		}
		
		// Output the listings
		foreach ($gs_indexList as $gs_indexAbbrev => $gs_indexItems) {
			if ($header == "on") {
				$gs_outputList .= '<a class="gs_indexAbbrevList" name="gs_indexAbbrevList_'. $gs_indexAbbrev .'" href="#gs_index">'. $gs_indexAbbrev .'</a>';
			}
			
			foreach($gs_indexItems as $gs_name => $gs_title) {
				$gs_outputList .= gs_display($gs_name, '', $inline, true) ."<br />";
			}
		}
		
		return $gs_outputList;
	}
}

if (!function_exists('gs_display'))
{
	function gs_display($gs_name, $gs_text = '', $gs_inline = '', $gs_showTerm = false)
	{
		$gs_data = gs_get_entry($gs_name);
		
		if (!isset($gs_inline) || empty($gs_inline)) {
			$gs_inline = get_option('gs_showInline', 'false');
		}

		if (!empty($gs_data)) {
			$gs_contents = $gs_data['contents'];
			
			// Are we adding paragraphs?
			if (get_option('gs_addParagraph', 'true') == 'true') {
				$gs_contents = wpautop($gs_contents);
			}
			
			// Make sure we process any shortcodes in the tooltip
			$gs_contents = do_shortcode($gs_contents);
			
			if ($gs_inline === 'false') {
				if (empty($gs_data['title'])) {
					$tippyTitle = $gs_name;
				} else {
					$tippyTitle = $gs_data['title'];
				}
				
				// Check width and height values
				$gs_dimensions = gs_get_dimensions($gs_data['dimensions']);
				
				$tippyValues = array(
					'header' => 'on',
					'title' => tippy_format_title($tippyTitle),
					'href' => $gs_data['link'],
					'text' => tippy_format_text($gs_contents),
					'class' => 'glossy_tip',
					'item' => 'glossy',
					'width' => $gs_dimensions['width'],
					'height' => $gs_dimensions['height']
				);
				
				$tippyLink = tippy_getLink($tippyValues);
				
				// Do we need to change the anchor text?
				if (!empty($gs_text)) {
					preg_match_all('/\<a [^\>]+\>([^\>]+)\<\/a\>/', $tippyLink, $gs_matchTitle);
					
					// Include angle brackets to ensure we are only replacing the anchor text
					$tippyLink = str_replace('>'. $gs_matchTitle[1][0] .'<', '>'. $gs_text .'<', $tippyLink);
				}
				
				return $tippyLink;
			} else {
				if ($gs_showTerm) {
					$gs_return = $gs_data['title'] .': '. $gs_data['contents'];
				} else {
					$gs_return = $gs_contents;
				}

				return $gs_return;
			}
		} else {
			return '';
		}
	}
}

// Return an array with all entry names.
// $format can be 'alpha' or 'list'
// 'alpha' returns an array of arrays with the first character as the key. Useful to list just the entries in a range of characters (A-C, etc)
// 'list' returns an array of all entries in one list, alphabetical order
if (!function_exists('gs_get_names')) {
	function gs_get_names($format = 'list')
	{
		global $wpdb;
		$gs_names_list = array();
		$gs_temp_list = array();
		
		$gs_tableName = $wpdb->prefix ."gs_store";
		
		$query = "SELECT gs_name, gs_title FROM ". $gs_tableName ." ORDER BY gs_name ASC;";
		$gs_data_arr = $wpdb->get_results($query, ARRAY_A);
		
		foreach($gs_data_arr AS $gs_entry) {
			$gs_name = $gs_entry['gs_name'];
			$gs_title = $gs_entry['gs_title'];
			
			if (empty($gs_title))
				$gs_title = $gs_name;
			
			// Put the entries in a new array to be sorted
			$gs_temp_list[$gs_title] = $gs_name;
		}
		
		ksort($gs_temp_list);
		
		foreach($gs_temp_list as $gs_title => $gs_name) {
			if ($format == 'list') {
				$gs_names_list[$gs_name] = $gs_title;
			} else {
				$gs_name_category = substr($gs_title, 0, 1);
				$gs_names_list[$gs_name_category][$gs_name] = $gs_title;
			}
		}
		
		return $gs_names_list;
	}
}

if (!function_exists('gs_get_entry')) {
	// gs_get_entry($entryName) returns an array with the entry data
	function gs_get_entry($entryName)
	{
		global $wpdb;
		$gs_tableName = $wpdb->prefix ."gs_store";
		
		$query = $wpdb->prepare("SELECT * FROM ". $gs_tableName ." WHERE gs_name = '%s';", $entryName);
		$gs_data_arr = $wpdb->get_results($query);

		if (!empty($gs_data_arr)) {
			$gs_data_obj = $gs_data_arr[0];
			
			$gs_data['name'] = $entryName;
			$gs_data['title'] = $gs_data_obj->gs_title;
			$gs_data['link'] = $gs_data_obj->gs_link;
			$gs_data['dimensions'] = $gs_data_obj->gs_dimensions;
			$gs_data['contents'] = $gs_data_obj->gs_contents;
			
			return $gs_data;
		} else {
			return '';
		}
	}
}

if (!function_exists('gs_get_dimensions'))
{
	function gs_get_dimensions($gs_dimensions)
	{
		$gs_dimensions_arr['width'] = false;
		$gs_dimensions_arr['height'] = false;
		
		// Validate the dimensions
		if (!empty($gs_dimensions)) {
			// Make sure to get the right case for the X
			$gs_dimensions = strtolower($gs_dimensions);
			
			$dimensions = explode("x", $gs_dimensions);
			
			// Clean up possible spaces
			$gs_dimensions_arr['width'] = trim($dimensions[0]);
			
			if (!empty($dimensions[1])) {
				$gs_dimensions_arr['height'] = trim($dimensions[1]);
			}
		}
		
		return $gs_dimensions_arr;
	}
}

if (!function_exists('gs_activatePlugin'))
{
	function gs_activatePlugin()
	{
		global $wpdb;
		$gs_tableName = $wpdb->prefix ."gs_store";
		
		/*
		 *
		 * Glossy entries will need to be stored in a new database table, gs_store.
		 *
		 * Table structure:
		 * gs_name varchar(255) not null primary key; unique name which serves as the unique identifier
		 * gs_title tinytext; title to display for Tippy. Optional. If blank, use gs_name
		 * gs_link tinytext; url to link Tippy title to. Optional. If blank, title will not be a link
		 * gs_dimensions varchar[12]; optional width X height setting to pass to Tippy. When only one value present, use for width.
		 * gs_contents medium text not null; contains the tooltip contents
		 *
		 */
		$query = "CREATE TABLE " . $gs_tableName . " (
				  gs_name varchar(255) NOT NULL,
				  gs_title tinytext,
				  gs_link tinytext,
				  gs_dimensions varchar(12),
				  gs_contents mediumtext NOT NULL,
				  PRIMARY KEY (gs_name)
				  ) CHARACTER SET UTF8;";
				  
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($query);
	}
}

if (!function_exists('gs_initPlugin')) {
	function gs_initPlugin()
	{
		global $tippy;

		wp_register_style('gs_style', plugins_url() .'/glossy/glossy.css');
		wp_enqueue_style('gs_style');

		// Load Tippy
		if (isset($tippy)) {
			$tippy->register_scripts();
			$tippy->register_styles();

			wp_enqueue_style('Tippy');
			wp_enqueue_script('Tippy');

			if ($tippy->getOption('dragTips')) {
	            wp_enqueue_script('jquery-ui-draggable');
	        }
	    }
	}
}

register_activation_hook(WP_PLUGIN_DIR . '/glossy/glossy.php', 'gs_activatePlugin');

add_shortcode('glossyindex', 'gs_indexShortcode');
add_filter('the_content', 'gs_scanContent');

add_action('wp_enqueue_scripts', 'gs_initPlugin');
add_action('admin_init', 'gs_initPlugin');

if (is_admin()) {
	require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.php');
}
?>