<?php
if (!function_exists('gs_export_panel')) {
	function gs_export_panel()
	{
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
	<h2>Export Entries</h2>
	<div style="margin-left: 15px; margin-bottom: 30px;">
		<form method="post" action="<?php echo admin_url('admin.php'); ?>">
			<?php wp_nonce_field('gs-export', 'gs_verify'); ?>
			<input type="hidden" name="action" value="glossy-export" />

			Choose how you want the export file to be formatted. If you have no preference, leave it at the default:<br /><br />

			<input type="radio" value="serial" name="gs_exportMethod" id="exportMethod_serial" checked="checked" /> <label for="exportMethod_serial">Serialized array</label><br />
			<input type="radio" value="json" name="gs_exportMethod" id="exportMethod_json" /> <label for="exportMethod_json">JSON object</label><br />
			<input type="radio" value="csv" name="gs_exportMethod" id="exportMethod_csv" /> <label for="exportMethod_csv">Comma separated values</label><br /><br />

			<input type="submit" class ="button button-primary" name="gs_update" value="Download Export File" /><br /><br />
		</form>
	</div>
</div>
		<?php
	}
}
?>