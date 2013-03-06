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

class Glossy {
	private static $glossy = false;

	public static function Instance()
	{
		if (!self::$glossy) {
			self::$glossy = new self();
		}

		return self::$glossy;
	}

	// Initialize everything
    private function __construct()
    {
        register_activation_hook(WP_PLUGIN_DIR . '/glossy/glossy.php', array($this, 'activatePlugin'));

		add_filter('the_content', array($this, 'scanContent'));

		add_action('wp_enqueue_scripts', array($this, 'initPlugin'));
		add_action('admin_init', array($this, 'initPlugin'));

		if (is_admin()) {
			add_action('admin_notices', array($this, 'tippy_check'));
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.php');
		}
    }

    public function tippy_check()
    {
    	global $tippy;

    	// Make sure $tippy is our object
		if (!is_object($tippy)) {
			echo '<div class="updated"><p><b>Notice:</b> The <a href="http://croberts.me/projects/wordpress-plugins/tippy-for-wordpress/" title="Tippy">Tippy</a> plugin appears to be missing or is outdated but is required to use Glossy. Please ensure both Glossy and Tippy are installed and up to date.</p></div>';
		}
    }

    public function activatePlugin()
	{
		global $wpdb;
		$gs_tableName = $wpdb->prefix ."gs_store";
		
		// See if table exists. If not, create it.
		$gs_tableCheck = $wpdb->get_var("SHOW TABLES LIKE '". $gs_tableName ."'");
		if ($gs_tableCheck != $gs_tableName) {
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
					  PRIMARY KEY  (gs_name)
					  ) CHARACTER SET UTF8;";

			echo $query;
					  
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($query);
		}
	}

	public function initPlugin()
	{
		global $tippy;

		wp_register_style('gs_style', plugins_url() .'/glossy/glossy.css');
		wp_enqueue_style('gs_style');

		// Load Tippy
		if (is_object($tippy)) {
			$tippy->register_scripts();
			$tippy->register_styles();

			wp_enqueue_style('Tippy');
			wp_enqueue_script('Tippy');

			if ($tippy->getOption('dragTips')) {
	            wp_enqueue_script('jquery-ui-draggable');
	        }
	    }
	}

    public function scanContent($content)
    {
        /* Look inside [gs rules] */
        $gs_expression  = '\[(gs|glossy|glossyindex)'; // Opening tag
        $gs_expression .= '\s([^\/\]]*)]'; // Grab contents inside the opening tag

        /* See if we have anything between [gs rules]???[/gs] */
        $gs_expression .= '(?:([^\[]*)'; // Content between opening and closing tag, if closing tag is present (next rule)
        $gs_expression .= '\[\/(?:glossy|gs)\])?'; // Closing tag, if present

        preg_match_all('/'. $gs_expression .'/', $content, $glossyMatches);

        $matches = $glossyMatches[0];
        $performing = $glossyMatches[1];
        $attributes = $glossyMatches[2];
        $titles = $glossyMatches[3];

        foreach ($attributes as $matchCount => $attributeSet) {
        	$gs_display = array();

        	// Search our attribute set for attribute names and values
        	preg_match_all('/([^=\s]*)(?:="(.*)")?/', $attributeSet, $attributeMatch);

        	$attributeNames = $attributeMatch[1];
        	$attributeValues = $attributeMatch[2];

        	foreach ($attributeNames as $attributeCount => $attribute) {
        		$attributeValue = $attributeValues[$attributeCount];
        		
        		// Loop through our attribute matches looking first for any attribute
        		// without a value - this is a standalone term. Any other attribute/value
        		// pair, add to our display array. It will ignore any incorrect attribute.
        		if (!empty($attribute) && empty($attributeValue)) {
        			$gs_display['term'] = $attribute;
        		} else {
        			$gs_display[$attribute] = trim($attributeValue);
        		}
        	}

        	if (!empty($titles[$matchCount])) {
        		$gs_display['title'] = trim($titles[$matchCount]);
        	}

        	if ($performing[$matchCount] == "glossyindex") {
        		$gs_tippy = $this->showIndex($gs_display);
        	} else {
	        	$gs_tippy = $this->display($gs_display);
	        }

	        $content = preg_replace('/'. preg_quote($matches[$matchCount], '/') .'/', $gs_tippy, $content, 1);
        }
        
        return $content;
    }

    private function showIndex($gs_display)
    {
    	$gs_indexList = $this->getNames('alpha');
		$gs_outputList = "";

		// Set up our index defaults
		if (!isset($gs_display['header']))
			$gs_display['header'] = 'on';

		if (!isset($gs_display['showTerm']))
			$gs_display['showTerm'] = 'true';

		// Display the header of first characters
		if ($gs_display['header'] == "on") {
			$gs_outputList .= '<div id="gs_index"><a name="gs_index"></a><span id="gs_indexTitle">Index:</span>';
			
			foreach ($gs_indexList as $gs_indexAbbrev => $gs_indexItems) {
				$gs_outputList .= '<a class="gs_indexAbbrev" href="#gs_indexAbbrevList_'. $gs_indexAbbrev .'">'. $gs_indexAbbrev .'</a>';
			}
			
			$gs_outputList .= '</div>';
		}
		
		// Output the listings
		foreach ($gs_indexList as $gs_indexAbbrev => $gs_indexItems) {
			if ($gs_display['header'] == "on") {
				$gs_outputList .= '<a class="gs_indexAbbrevList" name="gs_indexAbbrevList_'. $gs_indexAbbrev .'" href="#gs_index">'. $gs_indexAbbrev .'</a>';
			}
			
			foreach($gs_indexItems as $gs_name => $gs_title) {
				$gs_outputList .= $this->display($gs_display);
			}
		}
		
		return $gs_outputList;
    }

	public function indexShortcode($atts)
	{
		$showingIndex = true;
		
		extract(shortcode_atts(array(
			'header' => 'on',
			'inline' => 'false',
			'showTerm' => true,
			'beforeTerm' => '',
			'afterTerm' => '',
			'beforeDef' => '',
			'afterDef' => '',
			'defFirst' => false
		), $atts));
	}

	public function display($attributes)
	{
		global $tippy;

		// Grab our values from $attributes, setting defaults when needed
		$gs_term = isset($attributes['term']) ? $attributes['term'] : false;
		$gs_title = isset($attributes['title']) ? $attributes['title'] : false;
		$gs_inline = isset($attributes['inline']) ? $attributes['inline'] : get_option('gs_showInline', 'false');
		$gs_header = isset($attributes['header']) ? $attributes['header'] : get_option('gs_showHeader', 'on');
		$gs_showTerm = isset($attributes['showTerm']) ? $attributes['showTerm'] : false;

		$gs_data = $this->getEntry($gs_term);
		
		if (!empty($gs_data)) {
			$gs_contents = $gs_data['contents'];
			
			// Are we adding paragraphs?
			if (get_option('gs_addParagraph', 'true') == 'true') {
				$gs_contents = wpautop($gs_contents);
			}
			
			// Make sure we process any shortcodes in the tooltip
			$gs_contents = do_shortcode($gs_contents);
			
			if ($gs_inline === 'false') {
				if (is_object($tippy)) {
					if (empty($gs_data['title'])) {
						$tippyHeader = $gs_term;
					} else {
						$tippyHeader = $gs_data['title'];
					}

					if (!empty($gs_title)) {
						$tippyTitle = $gs_title;
					} else {
						$tippyTitle = $tippyHeader;
					}
					
					// Check width and height values
					$gs_dimensions = $this->getDimensions($gs_data['dimensions']);
					
					$tippyValues = array(
						'header' => $gs_header,
						'headertext' => tippy_format_title($tippyHeader),
						'title' => $tippyTitle,
						'href' => $gs_data['link'],
						'text' => tippy_format_text($gs_contents),
						'class' => 'glossy_tip',
						'item' => 'glossy',
						'width' => $gs_dimensions['width'],
						'height' => $gs_dimensions['height']
					);
				
					$tippyLink = $tippy->getLink($tippyValues);
				} else {
					$tippyLink = $gs_term;
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

	// Return an array with all entry names.
	// $format can be 'alpha' or 'list'
	// 'alpha' returns an array of arrays with the first character as the key. Useful to list just the entries in a range of characters (A-C, etc)
	// 'list' returns an array of all entries in one list, alphabetical order
	public function getNames($format = 'list')
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

	// getEntry($entryName) returns an array with the entry data
	public function getEntry($entryName)
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

	public function getDimensions($gs_dimensions)
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

	function saveEntry($entryName, $entryTitle, $entryLink, $entryDimensions, $entryContents, $entryAction, $entryOriginalName)
	{
		global $wpdb;
		$gs_tableName = $wpdb->prefix ."gs_store";
		
		$saveData = true;
		$errorFields = array();
		
		// Validate and sanitize data
		
		// Run checks on the name
		
		// See if the name is empty
		if (empty($entryName))
		{
			$saveData = false;
			$errorFields['entryName'] = 'empty';
		
		// Using a varchar(255) field; see if the name is too long to fit
		} else if (strlen($entryName) > 255) {
			$saveData = false;
			$errorFields['entryName'] = 'long';
		
		// See if the name is unique
		} else if ($entryAction == "Add" || $entryName != $entryOriginalName) {
			$query = $wpdb->prepare("SELECT gs_name FROM ". $gs_tableName ." WHERE gs_name = '%s';", $entryName);
			$existingName = $wpdb->get_var($query);
			
			if ($existingName)
			{
				$saveData = false;
				$errorFields['entryName'] = 'taken';
			}
		}
		
		// Check the link
		if (!empty($entryLink))
		{
			// Validate the url
			$urlCheck = filter_var($entryLink, FILTER_VALIDATE_URL);
			
			if (!$urlCheck)
			{
				$saveData = false;
				$errorFields['entryLink'] = 'invalid';
			}
		}
		
		// Validate the dimensions
		if (!empty($entryDimensions))
		{
			// Make sure to get the right case for the X
			$entryDimensions = strtolower($entryDimensions);
			
			$dimensions = explode("x", $entryDimensions);
			
			// Clean up possible spaces
			$dimensions[0] = trim($dimensions[0]);
			$dimensions[1] = trim($dimensions[1]);
			
			if (sizeof($dimensions) > 2 || !is_numeric($dimensions[0]) || (is_numeric($dimensions[0]) && intval($dimensions[0]) != $dimensions[0]) || (!empty($dimensions[1]) && (!is_numeric($dimensions[1]) || (is_numeric($dimensions[1]) && intval($dimensions[1]) != $dimensions[1]))))
			{
				$saveData = false;
				$errorFields['entryDimensions'] = 'invalid';
			}
		}
		
		// Make sure we have content
		if (empty($entryContents))
		{
			$saveData = false;
			$errorFields['entryContents'] = 'empty';
		}
		
		if ($saveData)
		{
			if ($entryAction == "Add")
			{
				$wpdb->insert($gs_tableName, array("gs_name" => $entryName, "gs_title" => $entryTitle, "gs_link" => $entryLink, "gs_dimensions" => $entryDimensions, "gs_contents" => $entryContents));
			} else {
				$wpdb->update($gs_tableName, array("gs_name" => $entryName, "gs_title" => $entryTitle, "gs_link" => $entryLink, "gs_dimensions" => $entryDimensions, "gs_contents" => $entryContents), array("gs_name" => $entryOriginalName));
			}
		}
		
		return $errorFields;
	}

	function deleteEntry($gs_name)
	{
		global $wpdb;
		$gs_tableName = $wpdb->prefix ."gs_store";
		
		$query = $wpdb->prepare("DELETE FROM ". $gs_tableName ." WHERE gs_name = '%s';", $gs_name);
		$entryDeleted = $wpdb->query($query);
		
		return $entryDeleted;
	}
}

$glossy = Glossy::Instance();

?>