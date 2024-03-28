<div class="settings-box emailintegration" id="box_id_{{ md5(lg_admin_settings_htmlemailsbox) }}">

	{!! renderPageheader(lg_admin_settings_htmlemailsbox) !!}

	<div class="card padded">
		<div class="fr">
			<div class="label tdlcheckbox">
				<label for="cHD_HTMLEMAILS">{{ lg_admin_settings_htmlemails }}</label>
			</div>
			<div class="control">
				<input type="checkbox" name="cHD_HTMLEMAILS" id="cHD_HTMLEMAILS" class="checkbox" value="1" {{ checkboxCheck(1, hs_setting('cHD_HTMLEMAILS')) }}>
				<label for="cHD_HTMLEMAILS" class="switch"></label>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label">
				<label for="cHD_HTMLEMAILS_EDITOR">{{ lg_admin_settings_htmlemails_editor }}</label>
				<div class="info">{{ lg_admin_settings_htmlemails_editorex }}</div>
			</div>
			<div class="control">
				<select name="cHD_HTMLEMAILS_EDITOR" onchange="">
					<option value="wysiwyg" {{ selectionCheck('wysiwyg', hs_setting('cHD_HTMLEMAILS_EDITOR')) }}>{{ lg_admin_settings_htmlemails_wysiwyg }}</option>
					<option value="markdown" {{ selectionCheck('markdown', hs_setting('cHD_HTMLEMAILS_EDITOR')) }}>{{ lg_admin_settings_htmlemails_markdown }}</option>
				</select>
			</div>
		</div>

	</div>

	<div class="button-bar space">
		<button type="submit" name="submit" class="btn accent">{{ lg_admin_settings_savebutton }}</button>
	</div>
</div>
