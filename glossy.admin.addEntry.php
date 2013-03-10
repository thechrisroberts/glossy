<?php
	if (!function_exists('gs_addEntry_panel'))
	{
		function gs_addEntry_panel()
		{
			$glossy = Glossy::Instance();

			$pageAction = "Add";
			$entryOriginalName = "";
			$entryName = "";
			$entryTitle = "";
			$entryLink = "";
			$entryDimensions = "";
			$entryContents = "";
			$saveEntry = array();
			$deleteEntry = false;
			$deletedEntryName = "";
			
			// gs_entry_action is saved in POST to tell if we are doing an Add or Edit.
			// Set $pageAction and $entryOriginalName accordingly.
			if (isset($_POST['gs_entry_action']))
			{
				$pageAction = $_POST['gs_entry_action'];
				$entryOriginalName = trim(stripslashes($_POST['gs_entry_original_name']));
			}
			
			// If gs_entry_update is set in POST, we are updating data. Pass values off to gs_save_entry
			// and check its response value for errors.
			//
			// If gs_edit_entry is set in GET, we are displaying an existing entry for editing. Load
			// the entry's data and present it to the user.
			//
			// If neither of these are set, we're adding a new entry. Present a blank form.
			if (isset($_POST['gs_entry_delete']))
			{
				$entryName = trim(stripslashes($_POST['gs_entry_name']));
				
				$deleteEntry = $glossy->deleteEntry($entryName);
				$deletedEntryName = $entryName;
				
				$pageAction = "Add";
				
				$entryName = "";
				$entryTitle = "";
				$entryLink = "";
				$entryDimensions = "";
				$entryContents = "";
			} else if (isset($_POST['gs_entry_update'])) {
				$entryName = trim(stripslashes($_POST['gs_entry_name']));
				$entryTitle = trim(stripslashes($_POST['gs_entry_title']));
				$entryLink = trim(stripslashes($_POST['gs_entry_link']));
				$entryDimensions = trim(stripslashes($_POST['gs_entry_dimensions']));
				$entryContents = trim(stripslashes($_POST['gs_entry_contents']));
				
				$saveEntry = $glossy->saveEntry($entryName, $entryTitle, $entryLink, $entryDimensions, $entryContents, $pageAction, $entryOriginalName);
				
				// If $saveEntry is empty (no errors) and we've been adding, switch to editing mode
				if (empty($saveEntry))
				{
					$completedAction = $pageAction;
					
					$pageAction = "Edit";
					$entryOriginalName = $entryName;
				}
			} else if (isset($_GET['gs_edit_entry'])) {
				$pageAction = "Edit";
				$entryName = stripslashes($_GET['gs_edit_entry']);
				
				$entryData = $glossy->getEntry($entryName);
				
				// See if the entry data loaded. If not, the entry name must not be valid.
				if (!empty($entryData))
				{
					$entryOriginalName = $entryName;
					$entryTitle = $entryData['title'];
					$entryLink = $entryData['link'];
					$entryDimensions = $entryData['dimensions'];
					$entryContents = $entryData['contents'];
				} else {
					echo '<div id="message" class="error">Unable to find Glossy Entry <i>'. $entryName .'</i></div>';
					$entryName = "";
				}
			}
			
			// Show the add/edit options
			?>
<div class="wrap">
	<h2><?php echo $pageAction; ?> Glossy Entry<?php if ($pageAction == "Edit") { echo " for <i>". $entryOriginalName ."</i>"; } ?></h2>
	<form method="post">

	<div style="margin-left: 30px;">
		<?php
			if (empty($saveEntry) && isset($_POST['gs_entry_update']))
			{
				echo '<div id="message" class="updated">Entry <i>'. $entryName .'</i> successfully '. strtolower($completedAction) .'ed.</div>';
			} else if (!empty($saveEntry)) {
				echo '<div id="message" class="error">Error trying to '. strtolower($pageAction) .' entry. See below for errors.</div>';
			} else if ($deleteEntry) {
				echo '<div id="message" class="updated">Successfully deleted entry <i>'. $deletedEntryName .'</i>.</div>';
			}
		?>
		
		<label for="gs_entry_name" title="Unique name to identify this entry">
			Entry Name
		</label> (Must be unique; used to identify this entry)<br />
		<input id="gs_entry_name" name="gs_entry_name" type="text" size="40" value="<?php echo $entryName; ?>" placeholder="Entry Name" required="required" />
		<?php
			if (isset($saveEntry['entryName']))
			{
				if ($saveEntry['entryName'] == "taken")
				{
					echo '<span class="gs_error">Name already taken</span>';
				} else if ($saveEntry['entryName'] == "long") {
					echo '<span class="gs_error">Name too long, 255 character max</span>';
				} else if ($saveEntry['entryName'] == "empty") {
					echo '<span class="gs_error">Name must be filled in</span>';
				}
			}
		?>
		<br /><br />
		
		<label for="gs_entry_title" title="Title to display in a Tippy tooltip. If blank, uses the entry name.">
			Entry Title
		</label> (Optional title to use for Tippy; if blank, the Entry Name is used)<br />
		<input id="gs_entry_title" name="gs_entry_title" type="text" size="40" value="<?php echo $entryTitle; ?>" placeholder="Entry Title" /><br /><br />
		
		<label for="gs_entry_link" title="URL link to use with the Tippy title, if desired.">
			Entry Link
		</label> (Optional URL to use as a link with the Tippy title)<br />
		<input id="gs_entry_link" name="gs_entry_link" type="text" size="40" value="<?php echo $entryLink; ?>" placeholder="Entry Link" />
		<?php
			if (isset($saveEntry['entryLink']))
			{
				if ($saveEntry['entryLink'] == "invalid")
				{
					echo '<span class="gs_error">This doesn\'t look like a valid link.</span>';
				}
			}
		?>
		<br /><br />
		
		<label for="gs_entry_dimensions" title="URL link to use with the Tippy title, if desired.">
			Entry Dimensions
		</label> (Optional way to specify dimensions of Tippy tooltip. Format: 500x400)<br />
		<input id="gs_entry_dimensions" name="gs_entry_dimensions" type="text" size="40" value="<?php echo $entryDimensions; ?>" placeholder="Entry Dimensions" />
		<?php
			if (isset($saveEntry['entryDimensions']))
			{
				if ($saveEntry['entryDimensions'] == "invalid")
				{
					echo '<span class="gs_error">Invalid dimension. Must be widthXheight or simply width</span>';
				}
			}
		?>
		<br /><br />
		
		<label for="gs_entry_contents" title="Body of the tooltip.">
			Entry Contents
		</label> (What should go inside the tooltip for this entry)<br />
		<?php
			if (isset($saveEntry['entryContents']))
			{
				if ($saveEntry['entryContents'] == "empty")
				{
					echo '<span class="gs_error">Entry must have content</span><br />';
				}
			}
		?>
		
		<div id="gs_entry_contents_wrapper">
			<?php wp_editor($entryContents, 'gs_entry_contents', array('textarea_name' => 'gs_entry_contents', 'textarea_rows' => 20)); ?>
		</div>
	</div>
	
	<br />
	
	<input type="hidden" name="gs_entry_original_name" value="<?php echo $entryOriginalName; ?>" />
	<input type="hidden" name="gs_entry_action" value="<?php echo $pageAction; ?>" />
	<input type="submit" class ="button button-primary" name="gs_entry_update" value="Save Entry" />
	<?php
		if ($pageAction == "Edit")
		{
			echo '<div style="display: inline-block; margin-left: 50px;"><input type="submit" name="gs_entry_delete" value="Delete Entry" onclick="return confirm(\'Are you sure you want to permanently delete entry '. $entryName .'?\');" /></div>';
			echo '<br /><br /><a href="admin.php?page=glossy-add-entry">Add new entry</a>';
		}
	?>
	<br />

	</form>
</div>
			<?php
		}
	}
?>