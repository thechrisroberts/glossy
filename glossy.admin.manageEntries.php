<?php
	if (!function_exists('gs_page_nav')) {
		function gs_page_nav($currentPage, $totalPages, $resultLimit, $totalResults)
		{
			echo '<div style="margin: 15px 0; font-size: 1.5em;">Page ';
			
			if ($totalPages <= 11) {
				for ($j = 1 ; $j <= 10 ; $j++) {
					$pageUrl = add_query_arg('gs_page', $j);
					
					if ($j == $currentPage)
						echo '<strong>';
						
					echo '<a style="margin: 0 5px;" href="'. $pageUrl .'">'. $j .'</a> ';
					
					if ($j == $currentPage)
						echo '</strong>';
				}
			} else {
				if ($currentPage > 1)
	    		{
	    			if ($currentPage > 2)
	    				echo '<a style="margin: 0 5px;" href="'. add_query_arg('gs_page', 1) .'">&laquo;</a> ';
	    			
	    			echo '<a style="margin: 0 5px;" href="'. add_query_arg('gs_page', ($currentPage - 1)) .'">&lsaquo;</a> ';
	    			
	    			if ($currentPage <= 3)
	    			{
	    				$counter = 1;
	    			} else {
	    				$counter = $currentPage - 3;
	    			}
	    			
	    			for ($i = $counter ; $i < $currentPage ; $i++)
	    			{
		    			echo '<a style="margin: 0 5px;" href="'. add_query_arg('gs_page', $i) .'">'. $i .'</a> ';
	    			}
	    		}
	    		
	    		echo '<span style="margin: 0 5px;">'. $currentPage .'</span>';
	    		
	    		if ($currentPage < $totalPages)
	    		{
	    			if ($currentPage > ($totalPages - 3))
	    			{
	    				$counter = $totalPages;
	    			} else {
	    				$counter = $currentPage + 3;
	    			}
	    			
	    			for ($i = ($currentPage + 1) ; $i <= $counter ; $i++)
	    			{
	    				echo '<a style="margin: 0 5px;" href="'. add_query_arg('gs_page', $i) .'">'. $i .'</a> ';
	    			}
	    			
	    			echo '<a style="margin: 0 5px;" href="'. add_query_arg('gs_page', ($currentPage + 1)) .'">&rsaquo;</a> ';
	    			
	    			if ($currentPage < $totalPages - 1)
		    			echo '<a style="margin: 0 5px;" href="'. add_query_arg('gs_page', $totalPages) .'">&raquo;</a> ';
	    		}
			}
			
			echo '</div>';
			
			?>
			<form method="post">
				<label>Jump to page: <input style="width: 50px;" type="text" name="gs_page" value="<?php echo $currentPage; ?>" /></label> <input type="submit" name="Jump" />
			</form><br />
			<?php
		}
	}
	
	if (!function_exists('gs_addEntry_panel'))
	{
		function gs_manageEntries_panel()
		{
			global $wpdb, $tippy;
			$gs_tableName = $wpdb->prefix ."gs_store";
			$gs_search_term = '';
			$gs_search_query = '';
			
			// See if we are changing options
			if (isset($_POST['gs_update']) && is_admin() && wp_verify_nonce($_POST['gs_verify'], 'gs-options')) {
				update_option('gs_showInline', $_POST['gs_showInline']);
				update_option('gs_addParagraph', $_POST['gs_addParagraph']);
		
				echo '<div class="updated"><p><strong>Your options have been updated.</strong></p></div>';
		    } else if (isset($_POST['gs_update'])) {
			    echo '<div class="updated"><p><strong>Security verification timed out, please try again.</strong></p></div>';
		    }
			
			// Get our search query
			if (!empty($_POST['gs_search_term'])) {
				$gs_search_term = $_POST['gs_search_term'];
				$gs_search_query = $wpdb->prepare(' WHERE gs_name LIKE "%%%s%%" OR gs_title LIKE "%%%s%%" OR gs_contents LIKE "%%%s%%"', $gs_search_term, $gs_search_term, $gs_search_term);
			}
			
			// Set the per-page limit
			if (!empty($_POST['gs_count_limit'])) {
				update_option('gs_count_limit', (int)$_POST['gs_count_limit']);
			}
			
			$gs_count_limit = get_option('gs_count_limit', 25);
			
			// See how many entries are present
			$query = "SELECT count(gs_name) FROM ". $gs_tableName . $gs_search_query .";";
			$gs_entry_count = $wpdb->get_var($query);
			
			$gs_entry_pages = ceil($gs_entry_count / $gs_count_limit);
			
			if (!empty($_POST['gs_page']) || !empty($_GET['gs_page'])) {
				$gs_page = (!empty($_POST['gs_page'])) ? (int)$_POST['gs_page'] : (int)$_GET['gs_page'];
			} else {
				$gs_page = 1;
			}
			
			if ($gs_page > $gs_entry_pages) {
				$gs_page = $gs_entry_pages;
			} else if ($gs_page < 1) {
				$gs_page = 1;
			}
			
			$gs_current = ($gs_page - 1) * $gs_count_limit;
			
			if ($gs_entry_count > 0) {
				// Load list of entries in alphabetical order
				$query = "SELECT gs_name FROM ". $gs_tableName . $gs_search_query ." ORDER BY gs_name LIMIT ". $gs_current .", ". $gs_count_limit .";";
				$gs_entry_list = $wpdb->get_results($query);
			}

			// Let's initialize Tippy for previews:
			$tippy->initialize_tippy();
?>
<style type="text/css">
	div.wrap {
		max-width: 700px;
	}
	
	span.gsOptionLabel {
		font-size: 18px;
	}
</style>

<div class="wrap">
	<h2>Manage Glossy Entries</h2>
	<?php gs_tippy_check(); ?>
	<div style="margin-left: 15px; margin-bottom: 30px;">
		<br /><span class="gsOptionLabel">Options</span><br /><br />
		
		<form method="post">
			<?php wp_nonce_field('gs-options', 'gs_verify'); ?>
			
			Should Glossy definitions show in a tooltip by default, or should the definitions be included inline? The Inline setting can be changed in the shortcode: inline="true" or inline="false" but you can specify a default setting here.<br /><br />
			
			<input id="gs_showInline_false" name="gs_showInline" value="false" type="radio" <?php if (get_option('gs_showInline', 'false') == 'false') echo "checked" ?> /> <label for="gs_showInline_false">Show definitions in a tooltip.</label><br />
			<input id="gs_showInline_true" name="gs_showInline" value="true" type="radio" <?php if (get_option('gs_showInline', 'false') == 'true') echo "checked" ?> /> <label for="gs_showInline_true">Show definitions inline.</label><br /><br />
			
			WordPress automatically applies &lt;p&gt; tags to post content whenever it encounters two line breaks, but this does not happen with Glossy definitions. The following option lets you decide whether or not to apply this behavior to your definitions.<br /><br />
			
			<input id="gs_addParagraph_true" name="gs_addParagraph" value="true" type="radio" <?php if (get_option('gs_addParagraph', 'true') == 'true') echo "checked" ?> /> <label for="gs_addParagraph_true">Automatically split into paragraphs.</label><br />
			<input id="gs_addParagraph_false" name="gs_addParagraph" value="false" type="radio" <?php if (get_option('gs_addParagraph', 'true') == 'false') echo "checked" ?> /> <label for="gs_addParagraph_false">Do not split into paragraphs.</label><br /><br />
			
			<input type="submit" name="gs_update" value="Update Options" /><br /><br />
		</form>
		
		<br /><span class="gsOptionLabel">View Entries</span><br /><br />
		
		<form method="post">
			<label>Search for entry: <input type="text" name="gs_search_term" value="<?php echo $gs_search_term; ?>" /></label><input type="submit" name="Search" />
		</form>
		
		<?php if ($gs_entry_count == 0 && !empty($gs_search_term)) { ?>
			No entries were found matching your search. Try again with a new search string.
		<?php } ?>
	</div>
	
	<div style="margin-left: 30px;">
		<ol start="<?php echo (($gs_page - 1) * $gs_count_limit) + 1; ?>">
			<?php
				if ($gs_entry_count == 0 && empty($gs_search_term))
				{
					echo '<div id="message" class="updated">No Glossy entries found. You may want to <a href="admin.php?page=glossy-add-entry">add an entry</a>.</div>';
				} else if ($gs_entry_count > 0) {
					foreach($gs_entry_list as $gs_name_arr)
					{
						$gs_name = $gs_name_arr->gs_name;
						
						echo '<li><a href="admin.php?page=glossy-add-entry&gs_edit_entry='. urlencode($gs_name) .'">'. $gs_name .'</a><br />Preview: '. gs_display($gs_name, '', 'false') .'<br /></li>';
					}
				}
			?>
		</ol>
	</div>
	
	<div style="margin-left: 15px; margin-top: 30px;">
		<?php if ($gs_entry_pages > 1) {
			gs_page_nav($gs_page, $gs_entry_pages, $gs_count_limit, $gs_entry_count);
		} ?>
		
		<?php
		if ($gs_entry_count > 0) {
			$resultsCountStart = ((($gs_page - 1) * $gs_count_limit) + 1);
			$resultsCountEnd = (($gs_page * $gs_count_limit) > $gs_entry_count) ? $gs_entry_count : ($gs_page * $gs_count_limit);
			
			echo 'Viewing page '. $gs_page .' of '. $gs_entry_pages .'; results '. $resultsCountStart .'-'. $resultsCountEnd .' of '. $gs_entry_count .'.<br />';
		}
		?>
		
		<form method="post">
			<label>Results per page: <input style="width: 50px;" type="text" name="gs_count_limit" value="<?php echo $gs_count_limit; ?>" /></label> <input type="submit" name="Update" />
		</form><br />
	</div>
</div>
<?php
		}
	}
?>