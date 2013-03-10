<?php
if (!function_exists('gs_import_panel')) {
	function gs_import_panel()
	{
		$gs_importStatus = get_option('gs_importStatus', '');
		delete_option('gs_importStatus');
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
	<h2>Import Entries</h2>
	<div style="margin-left: 15px; margin-bottom: 30px;">
		<?php if ($gs_importStatus == 'complete') { ?>
			<h3>Import completed successfully.</h3>
		<?php } else if (is_array($gs_importStatus)) { ?>
			<h3>Import completed but with errors. See below for details.</h3>
		<?php } ?>
		<form method="post" action="<?php echo admin_url('admin.php'); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field('gs-import', 'gs_verify'); ?>
			<input type="hidden" name="action" value="glossy-import" />

			What kind of file are you importing? If this was originally exported from Glossy, the type should be abbreviated in the filename.<br /><br />

			<input type="radio" value="serial" name="gs_importMethod" id="importMethod_serial" checked="checked" /> <label for="importMethod_serial">Serialized array</label><br />
			<input type="radio" value="json" name="gs_importMethod" id="importMethod_json" /> <label for="importMethod_json">JSON object</label><br />
			<input type="radio" value="csv" name="gs_importMethod" id="importMethod_csv" /> <label for="importMethod_csv">Comma separated values</label><br /><br />
			
			<input type="file" name="gs_import" id="file"><br /><br />
			<input name="import" class="button button-primary" type="submit" value="Upload File for Import" />
		</form>
		<?php
		if (is_array($gs_importStatus)) {
			echo '<br /><br />';

			foreach ($gs_importStatus as $gs_name => $gs_error) {
				if ($gs_error == 'taken') {
					echo 'Failed to import term <b>'. $gs_name .'</b>: term name already taken.<br />';
				} else {
					echo 'Failed to import term <b>'. $gs_name .'</b>: there was an error with its stored data.<br />';
				}
			}
		}
		?>
	</div>
</div>
		<?php
	}
}
?>