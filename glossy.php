<?php
/*
Plugin Name: Glossy
Plugin URI: http://croberts.me/glossy/
Description: Makes it easy to create site-wide glossary or dictionary entries which pop up using the Tippy plugin
Version: 2.3.5
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
	private $tableName = ''; 

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
        global $wpdb;
        $this->tableName = $wpdb->prefix ."gs_store";

        register_activation_hook(WP_PLUGIN_DIR . '/glossy/glossy.php', array($this, 'activatePlugin'));

		add_filter('the_content', array($this, 'scanContent'), 10);
		add_shortcode('gs', array($this, 'glossyShortcode'), 11);
		add_shortcode('glossy', array($this, 'glossyShortcode'), 11);

		add_action('wp_enqueue_scripts', array($this, 'initPlugin'));

		if (is_admin()) {
			add_action('admin_notices', array($this, 'tippy_check'));
			add_action('admin_action_glossy-export', array($this, 'exportEntries'));
			add_action('admin_action_glossy-import', array($this, 'importEntries'));
			require_once(WP_PLUGIN_DIR . '/glossy/glossy.admin.php');
		}
    }

    public function tippy_check()
    {
    	// Make sure Tippy is loaded
		if (!method_exists('Tippy', 'getOption')) {
			echo '<div class="updated"><p><b>Notice:</b> The <a href="http://croberts.me/projects/wordpress-plugins/tippy-for-wordpress/" title="Tippy">Tippy</a> plugin appears to be missing or is outdated but is required to use Glossy. Please ensure both Glossy and Tippy are installed and up to date.</p></div>';
		}
    }

    public function activatePlugin()
	{
		global $wpdb;
		
		// See if table exists. If not, create it.
		$gs_tableCheck = $wpdb->get_var("SHOW TABLES LIKE '". $this->tableName ."'");
		if ($gs_tableCheck != $this->tableName) {
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
			$query = "CREATE TABLE " . $this->tableName . " (
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
		wp_register_style('gs_style', plugins_url() .'/glossy/glossy.css');
		wp_enqueue_style('gs_style');

		// Load Tippy
		if (method_exists('Tippy', 'getOption')) {
			Tippy::register_scripts();
			Tippy::register_styles();

			wp_enqueue_style('Tippy');
			wp_enqueue_script('Tippy');

			if (Tippy::getOption('dragTips')) {
	            wp_enqueue_script('jquery-ui-draggable');
	        }
	    }
	}

    public function scanContent($content)
    {
        /* Look inside [gs rules] */
        $gs_expression  = '(?<!\[)\[(gs|glossy|glossyindex)'; // Opening tag
        $gs_expression .= '(?:\s([^\]]*))?]'; // Grab contents inside the opening tag

        /* See if we have anything between [gs rules]???[/gs] */
        $gs_expression .= '(?:([^\[]*)'; // Content between opening and closing tag, if closing tag is present (next rule)
        $gs_expression .= '\[\/(?:glossy|gs)\])?'; // Closing tag, if present

        preg_match_all('/'. $gs_expression .'/i', $content, $glossyMatches);

        $matches = $glossyMatches[0];
        $performing = $glossyMatches[1];
        $attributes = $glossyMatches[2];
        $titles = $glossyMatches[3];

        foreach ($attributes as $matchCount => $attributeSet) {
        	$gs_display = array();

        	// Check first to see if the attribute contains an =; if not, we can assume it is just a term
        	if ($performing[$matchCount] != "glossyindex" && !strpos($attributeSet, '=')) {
        		$gs_display['term'] = $attributeSet;
        	} else {
	        	// Search our attribute set for attribute names and values
	        	$attribute_match  = '([^=\s]*)'; // Match anything not an equal sign or space
	        	$attribute_match .= '(?:=(?:"|\')'; // Open a non-matching set starting with an = followed by ' or "
	        	$attribute_match .= '([^"\']*)'; // Catch anything between '' or ""
	        	$attribute_match .= '(?:"|\'))?'; // Get the closing ' or " and close the match

				preg_match_all('/'. $attribute_match .'/', $attributeSet, $attributeMatch);

	        	$attributeNames = $attributeMatch[1];
	        	$attributeValues = $attributeMatch[2];

	        	foreach ($attributeNames as $attributeCount => $attribute) {
	        		$attributeValue = $attributeValues[$attributeCount];
	        		
	        		// Loop through our attribute matches looking first for any attribute
	        		// without a value - this is a standalone term. Any other attribute/value
	        		// pair, add to our display array. It will ignore any incorrect attribute.
	        		if (!empty($attribute)) {
		        		if (empty($attributeValue)) {
		        			$gs_display['term'] = $attribute;
		        		} else {
		        			$gs_display[$attribute] = trim($attributeValue);
		        		}
		        	}
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

	        $content = str_replace($matches[$matchCount], $gs_tippy, $content);
        }
        
        return $content;
    }

    public function glossyShortcode($atts, $content = '')
    {
    	// Rebuild string to pass to scanContent
    	$shortcodeString = '[glossy ';

    	foreach ($atts as $term => $termValue) {
    		$shortcodeString .= $term .'="'. $termValue .'" ';
    	}

    	$shortcodeString .= ']';

    	if (!empty($content)) {
    		$shortcodeString .= $content . '[/glossy]';
    	}

    	return $this->scanContent($shortcodeString);
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

		if (!isset($gs_display['beforeDef']))
			$gs_display['beforeDef'] = '';

		if (!isset($gs_display['afterDef']))
			$gs_display['afterDef'] = '<br />';

		if (!isset($gs_display['beforeTerm']))
			$gs_display['beforeTerm'] = '';

		if (!isset($gs_display['afterTerm']))
			$gs_display['afterTerm'] = ': ';

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
				$gs_display['term'] = $gs_name;
				$gs_outputList .= $gs_display['beforeDef'] . $this->display($gs_display) . $gs_display['afterDef'];
			}
		}
		
		return $gs_outputList;
    }

	public function display($attributes)
	{
		// Grab our values from $attributes, setting defaults when needed
		$gs_term = isset($attributes['term']) ? $attributes['term'] : false;
		$gs_title = isset($attributes['title']) ? $attributes['title'] : false;
		$gs_inline = isset($attributes['inline']) ? $attributes['inline'] : get_option('gs_showInline', 'false');
		$gs_header = isset($attributes['header']) ? $attributes['header'] : get_option('gs_showHeader', 'on');
		$gs_showTerm = isset($attributes['showTerm']) ? $attributes['showTerm'] : 'false';

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
				if (method_exists('Tippy', 'getOption')) {
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
					
					$tippyValues = $attributes;

					$tippyValues['header'] = $gs_header;
					$tippyValues['headertext'] = $tippyHeader;
					$tippyValues['title'] = $tippyTitle;
					$tippyValues['text'] = $gs_contents;
					$tippyValues['class'] = isset($attributes['class']) ? $attributes['class'] .'glossy_tip' : 'glossy_tip';
					
					if (isset($gs_data['link']) && !empty($gs_data['link'])) {
						$tippyValues['href'] = $gs_data['link'];
					}
					
					if ($gs_dimensions['width']) {
						$tippyValues['width'] = $gs_dimensions['width'];
					}
					
					if ($gs_dimensions['height']) {
						$tippyValues['height'] = $gs_dimensions['height'];
					}
					
					$tippyLink = Tippy::getLink($tippyValues);
				} else {
					$tippyLink = $gs_term;
				}
				
				return $tippyLink;
			} else {
				$showName = $gs_data['title'];

				if (empty($showName))
					$showName = $gs_data['name'];

				if ($gs_showTerm == 'true') {
					$gs_return = $attributes['beforeTerm'] . $showName . $attributes['afterTerm'] . $gs_data['contents'];
				} else {
					$gs_return = $gs_data['contents'];
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
		
		$query = "SELECT gs_name, gs_title FROM ". $this->tableName ." ORDER BY gs_name ASC;";
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
				// $gs_name_category = strtoupper(substr($gs_title, 0, 1));
				$gs_name_category = mb_strtoupper(mb_substr($gs_title, 0, 1, "UTF-8"), "UTF-8");
				$gs_names_list[$gs_name_category][$gs_name] = $gs_title;
			}
		}
		
		return $gs_names_list;
	}

	// getEntry($entryName) returns an array with the entry data
	public function getEntry($entryName)
	{
		global $wpdb;
		
		$query = $wpdb->prepare("SELECT * FROM ". $this->tableName ." WHERE gs_name = '%s';", $entryName);
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

	public function saveEntry($entryName, $entryTitle, $entryLink, $entryDimensions, $entryContents, $entryAction, $entryOriginalName = '')
	{
		global $wpdb;
		
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
			$query = $wpdb->prepare("SELECT gs_name FROM ". $this->tableName ." WHERE gs_name = '%s';", $entryName);
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
				$wpdb->insert($this->tableName, array("gs_name" => $entryName, "gs_title" => $entryTitle, "gs_link" => $entryLink, "gs_dimensions" => $entryDimensions, "gs_contents" => $entryContents));
			} else {
				$wpdb->update($this->tableName, array("gs_name" => $entryName, "gs_title" => $entryTitle, "gs_link" => $entryLink, "gs_dimensions" => $entryDimensions, "gs_contents" => $entryContents), array("gs_name" => $entryOriginalName));
			}
		}
		
		return $errorFields;
	}

	public function deleteEntry($gs_name)
	{
		global $wpdb;
		
		$query = $wpdb->prepare("DELETE FROM ". $this->tableName ." WHERE gs_name = '%s';", $gs_name);
		$entryDeleted = $wpdb->query($query);
		
		return $entryDeleted;
	}

	public function importEntries()
	{
		$importMethod = $_POST['gs_importMethod'];

		if (file_exists($_FILES["gs_import"]["tmp_name"])) {
			$importFile = file_get_contents($_FILES["gs_import"]["tmp_name"]);
			$this->performImport($importMethod, $importFile);
		} else {
			wp_redirect(admin_url('admin.php?page=glossy-import') ."&glossy_import=failed");
		}
	}

	private function performImport($importMethod, $importFile)
	{
		$importArr = array();
		$importFailed = array();

		if ($importMethod == 'json') {
			$importArr = json_decode($importFile, true);
		} else if ($importMethod == 'serial') {
			$importArr = unserialize($importFile);
		} else if ($importMethod == 'csv') {
			$processArr = explode("\r\n", $importFile);

			foreach ($processArr as $entryLine) {
				if (!empty($entryLine)) {
					list($gs_name, $gs_title, $gs_link, $gs_dimensions, $gs_contents) = explode(", ", $entryLine, 5);

					$storeText = str_replace('&#44;', ',', $gs_contents);
					$storeText = str_replace('|NNEWLINE|', "\n", $storeText);
					$storeText = str_replace('|RNEWLINE|', "\r", $storeText);

					$importArr[] = array('name' => str_replace('&#44;', ',', $gs_name), 
										 'title' => str_replace('&#44;', ',', $gs_title), 
										 'link' => str_replace('&#44;', ',', $gs_link), 
										 'dimensions' => str_replace('&#44;', ',', $gs_dimensions), 
									 	 'contents' => $storeText);
				}
			}
		}

		if (is_array($importArr) && !empty($importArr)) {
			foreach ($importArr as $gs_entry) {
				$gs_name = $gs_entry['name'];
				$gs_title = $gs_entry['title'];
				$gs_link = $gs_entry['link'];
				$gs_dimensions = $gs_entry['dimensions'];
				$gs_contents = $gs_entry['contents'];
				
				$importError = $this->saveEntry($gs_name, $gs_title, $gs_link, $gs_dimensions, $gs_contents, 'Add');

				if (!empty($importError)) {
					if ($importError['entryName'] == 'taken') {
						$importFailed[$gs_name] = 'taken';
					} else {
						$importFailed[$gs_name] = 'failed';
					}
				}
			}
		}

		if (!empty($importFailed)) {
			update_option('gs_importStatus', $importFailed);
		} else {
			update_option('gs_importStatus', 'complete');
		}

		wp_redirect(admin_url('admin.php?page=glossy-import'));
	}

	public function exportEntries()
	{
		$exportMethod = isset($_POST['gs_exportMethod']) ? $_POST['gs_exportMethod'] : 'csv';

		$this->performExport($exportMethod);
	}

	private function performExport($exportMethod)
	{
		global $wpdb;
		
		$exportGlobalArr = array();
		$exportText = '';

		// Load our list of entries
		$query = "SELECT gs_name, gs_title, gs_link, gs_dimensions, gs_contents FROM ". $this->tableName ." ORDER BY gs_name ASC;";
		$gs_data_arr = $wpdb->get_results($query, ARRAY_A);
		
		foreach($gs_data_arr AS $gs_entry) {
			$exportArray = array();

			// For the sake of csv, convert any commas to &#44;
			$exportArray['name'] = $gs_entry['gs_name'];
			$exportArray['title'] = $gs_entry['gs_title'];
			$exportArray['link'] = $gs_entry['gs_link'];
			$exportArray['dimensions'] = $gs_entry['gs_dimensions'];
			$exportArray['contents'] = $gs_entry['gs_contents'];

			if ($exportMethod == "json") {
				$exportGlobalArr[] = $exportArray;
			} else if ($exportMethod == "serial") {
				$exportGlobalArr[] = $exportArray;
			} else if ($exportMethod == "csv") {
				$storeText = str_replace(',', '&#44;', $gs_entry['gs_contents']);
				$storeText = str_replace("\n", '|NNEWLINE|', $storeText);
				$storeText = str_replace("\r", '|RNEWLINE|', $storeText);
				$exportText .= str_replace(',', '&#44;', $gs_entry['gs_name']) .", ". str_replace(',', '&#44;', $gs_entry['gs_title']) .", ". str_replace(',', '&#44;', $gs_entry['gs_link']) .", ". str_replace(',', '&#44;', $gs_entry['gs_dimensions']) .", ". $storeText ."\r\n";
			}
		}

		if ($exportMethod == "json") {
			$exportText .= json_encode($exportGlobalArr) ."\r\n";
		} else if ($exportMethod == "serial") {
			$exportText .= serialize($exportGlobalArr) ."\r\n";
		}

		$exportName = date('Y-m-d') ."_glossy_terms.". $exportMethod .".txt";

		header('Content-type: application/txt');
		header('Content-Disposition: attachment; filename="'. $exportName .'"');

		echo $exportText;

		die();
	}
}

$glossy = Glossy::Instance();

?>