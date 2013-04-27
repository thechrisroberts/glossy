<?php
	if (!function_exists('gs_options_panel'))
	{
		function gs_options_panel()
		{
			// See if we are changing options
			if (isset($_POST['gs_update']) && is_admin() && wp_verify_nonce($_POST['gs_verify'], 'gs-options')) {
				update_option('gs_showInline', $_POST['gs_showInline']);
				update_option('gs_addParagraph', $_POST['gs_addParagraph']);
				update_option('gs_showHeader', $_POST['gs_showHeader']);
				update_option('gs_access', $_POST['gs_access']);
				$useDivContent = isset($_POST['gs_useDivContent']) ? 'true' : 'false';
				update_option('gs_useDivContent', $useDivContent);
		
				echo '<div class="updated"><p><strong>Your options have been updated.</strong></p></div>';
		    } else if (isset($_POST['gs_update'])) {
			    echo '<div class="updated"><p><strong>Security verification timed out, please try again.</strong></p></div>';
		    }
?>
<style type="text/css">
	div.wrap {
		max-width: 700px;
	}
	
	span.gsOptionLabel {
		font-size: 18px;
	}
</style>

<style type="text/css">
	div.wrap {
		max-width: 700px;
	}
	
	span.gsOptionLabel {
		font-size: 18px;
	}
</style>

<div class="wrap">
	<h2>Glossy Options</h2>
	<div style="margin-left: 15px; margin-bottom: 30px;">
		<form method="post">
			<?php wp_nonce_field('gs-options', 'gs_verify'); ?>
			
			Should Glossy definitions show in a tooltip by default, or should the definitions be included inline? The Inline setting can be changed in the shortcode: inline="true" or inline="false" but you can specify a default setting here.<br /><br />
			
			<input id="gs_showInline_false" name="gs_showInline" value="false" type="radio" <?php if (get_option('gs_showInline', 'false') == 'false') echo "checked" ?> /> <label for="gs_showInline_false">Show definitions in a tooltip.</label><br />
			<input id="gs_showInline_true" name="gs_showInline" value="true" type="radio" <?php if (get_option('gs_showInline', 'false') == 'true') echo "checked" ?> /> <label for="gs_showInline_true">Show definitions inline.</label><br /><br />
			
			WordPress automatically applies &lt;p&gt; tags to post content whenever it encounters two line breaks, but this does not happen with Glossy definitions. The following option lets you decide whether or not to apply this behavior to your definitions.<br /><br />
			
			<input id="gs_addParagraph_true" name="gs_addParagraph" value="true" type="radio" <?php if (get_option('gs_addParagraph', 'true') == 'true') echo "checked" ?> /> <label for="gs_addParagraph_true">Automatically split into paragraphs.</label><br />
			<input id="gs_addParagraph_false" name="gs_addParagraph" value="false" type="radio" <?php if (get_option('gs_addParagraph', 'true') == 'false') echo "checked" ?> /> <label for="gs_addParagraph_false">Do not split into paragraphs.</label><br /><br />

			By default, should all your Glossy entries show the term title in the tooltip? This can be set on a per-term basis with header="on/off" - ie, [gs nyse header="off"]<br /><br />
			
			<input id="gs_showHeader_on" name="gs_showHeader" value="on" type="radio" <?php if (get_option('gs_showHeader', 'on') == 'on') echo "checked" ?> /> <label for="gs_showHeader_on">Show headers by default.</label><br />
			<input id="gs_showHeader_off" name="gs_showHeader" value="off" type="radio" <?php if (get_option('gs_showHeader', 'on') == 'off') echo "checked" ?> /> <label for="gs_showHeader_off">Do not show headers by default.</label><br /><br />

			What degree of user access is necessary to manage Glossy entries (add/edit/delete entries)? 
			<select name="gs_access">
				<option value="admin"<?php if (get_option('gs_access', 'admin') == 'admin') { echo ' selected="selected"'; } ?>>Administrator</option>
				<option value="editor"<?php if (get_option('gs_access', 'admin') == 'editor') { echo ' selected="selected"'; } ?>>Editor</option>
				<option value="contributor"<?php if (get_option('gs_access', 'admin') == 'contributor') { echo ' selected="selected"'; } ?>>Contributor</option>
			</select><br /><br />

			Should Glossy use the experimental content method of Tippy? In some cases, this will allow Glossy content to work that would otherwise fail.<br /><br />

			<input id="gs_useDivContent" name="gs_useDivContent" type="checkbox" value="true" <?php if (get_option('gs_useDivContent', 'false') === 'true') echo "checked" ?> /> 
			<label for="gs_useDivContent">
				Use new content method
			</label><br /><br />
			
			<input type="submit" class ="button button-primary" name="gs_update" value="Update Options" /><br /><br />
		</form>
	</div>
</div>
<?php
		}
	}
?>