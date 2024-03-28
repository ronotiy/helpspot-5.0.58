<?php
/*
NOTE: Parts of this template can be customized to control different aspects of request creation. Read the details below.

PREFILLING FORM FIELDS:
You can prefill any form field by passing in URL encoded values via GET. For example, in your intranet you may add a link
to HelpSpot that looks like this:

http://www.example.com/support/index.php?pg=request&fullname=Bob+Smith&sUserId=453232&sEmail=bsmith%40example.com&additional=SAP+ID%3A844883

This would set the fields to:
fullname: Bob Smith
sEmail: bsmith@example.com
additional: SAP ID:844883

Make sure to read the details on the 'additional' field. It's very useful for sending extra information into HelpSpot about a request
*/

//Set page title
$this->assign('pg_title', lg_portal_request);

//Set onload
$this->assign('pg_onload', 'ShowCategoryCustomFields();');

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<h1><?php echo lg_portal_request ?></h1>
<br />
<?php echo lg_portal_req_note ?>
<br />

<?php echo $this->helper->showError('general', '<br />') ?>

<form action="index.php?pg=request" method="post" enctype="multipart/form-data">
	<?php echo csrf_field(); ?>

	<?php /* Any field names listed in the 'required' hidden field will be checked by HelpSpot to make sure they're not empty */ ?>
	<input type="hidden" name="required" value="sEmail,fullname" />

	<?php
	/* The 'additional' hidden field can be used to pass hidden information in the request. This is best used when
you pass in information as described above. You can pass in additional=otherdetails and those details will be passed
into HelpSpot with the rest of the request. NOTE: Customers can see the additional information on the check screen.
It is also emailed to them */ ?>
	<input type="hidden" name="additional" value="<?php echo $this->request_additional ?>" />

	<?php
	/*
Use this field to submit the customer ID to HelpSpot. You may need to change the variable lg_portal_req_account
in your language file to match what your organization calls the field (Account ID, User ID, etc)

<p><label for="sUserId" class="datalabel"><?php echo lg_portal_req_account ?></label><br />
    <?php echo $this->helper->showError('sUserId','<br />') ?>
    <input type="text" name="sUserId" size="40" maxlength="250" value="<?php echo $this->request_sUserId ?>" />
</p>
*/
	?>

	<?php /* HelpSpot will automatically parse the name into first name and last name */ ?>
	<p><label for="fullname" class="datalabel required"><?php echo lg_portal_req_name ?></label><br />
		<?php echo $this->helper->showError('fullname', '<br />') ?>
		<input type="text" name="fullname" id="fullname" size="40" maxlength="100" value="<?php echo $this->request_fullname ?>" />
	</p>

	<?php
	/*
If you would rather use individual first name and last name fields then uncomment these fields
and comment out the fullname field above. You also need to remove the 'fullname' item from the
hidden field 'required' above.


<p><label for="sFirstName" class="datalabel"><?php echo lg_portal_req_firstname ?></label><br />
    <?php echo $this->helper->showError('sFirstName','<br />') ?>
    <input type="text" name="sFirstName" size="40" maxlength="100" value="<?php echo $this->request_sFirstName ?>" />
</p>

<p><label for="sLastName" class="datalabel"><?php echo lg_portal_req_lastname ?></label><br />
    <?php echo $this->helper->showError('sLastName','<br />') ?>
    <input type="text" name="sLastName" size="40" maxlength="100" value="<?php echo $this->request_sLastName ?>" />
</p>
*/
	?>

	<p><label for="sEmail" class="datalabel required"><?php echo lg_portal_req_email ?></label><br />
		<?php echo $this->helper->showError('sEmail', '<br />') ?>
		<input type="text" name="sEmail" id="sEmail" size="40" maxlength="250" value="<?php echo $this->request_sEmail ?>" />
	</p>

	<?php if ($this->hd_allowCc) : ?>
		<p><label for="sCC" class="datalabel"><?php echo lg_portal_req_cc_email ?></label><br />
			<?php echo $this->helper->showError('sCC', '<br />') ?>
			<input type="text" id="sCC" name=" sCC" size="40" maxlength="250" value="<?php echo $this->request_sCC ?>" />
		</p>
	<?php endif; ?>

	<p><label for="sPhone" class="datalabel"><?php echo lg_portal_req_phone ?></label><br />
		<?php echo $this->helper->showError('sPhone', '<br />') ?>
		<input type="text" id="sPhone" name="sPhone" size="40" maxlength="250" value="<?php echo $this->request_sPhone ?>" />
	</p>

	<div class="requestwrap">
		<div class="forumoption"><?php echo lg_portal_req_detailsheader ?></div>

		<?php if ($this->hd_allowSubject) : ?>
			<p><label for="sTitle" class="datalabel"><?php echo lg_portal_req_subject ?></label><br />
				<?php echo $this->helper->showError('sTitle', '<br />') ?>
				<input type="text" id="sTitle" name="sTitle" size="40" maxlength="250" value="<?php echo $this->request_sTitle ?>" />
			</p>
		<?php endif; ?>

		<p><label for="fUrgent" class="datalabel"><?php echo lg_portal_req_urgent ?></label><br />
			<select name="fUrgent" id="fUrgent">
				<option value="0" <?php if ($this->request_fUrgent == 0) {
										echo 'selected';
									} ?>><?php echo lg_portal_req_no ?></option>
				<option value="1" <?php if ($this->request_fUrgent == 1) {
										echo 'selected';
									} ?>><?php echo lg_portal_req_yes ?></option>
			</select>
		</p>

		<?php //Show categories for the visitor to choose from if any categories have been made public.
		//Defaults to empty (inbox)
		?>
		<?php if ($this->splugin('Categories', 'count')) : ?>
			<p><label for="xCategory" class="datalabel"><?php echo lg_portal_req_category ?></label><br />
				<select name="xCategory" id="xCategory" onchange="ShowCategoryCustomFields();">
					<option value=""></option>
					<?php foreach ($this->splugin('Categories', 'getCategories') as $category) : ?>
						<option value="<?php echo $category['xCategory'] ?>" <?php if ($this->request_xCategory == $category['xCategory']) {
																							echo 'selected';
																						} ?>>
							<?php echo $category['sCategory'] ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php /* This code can be used to include category groupings in the category dropdown.
            To use this code, delete everything in the `p` tag above and uncomment below.. */
				?>
			<?php /* <-- Delete this entire line
        <p><label for="xCategory" class="datalabel"><?php echo lg_portal_req_category ?></label><br />
            <select name="xCategory" id="xCategory" onchange="ShowCategoryCustomFields();">
                <option value=""></option>
        <?php $cats = $this->splugin('Categories','getCategories');
            $cats = array_sort($cats, function($cat) {
                return $cat['sCategoryGroup'];
            });
            $current_group = '';
            ?>
            <?php foreach($cats AS $category): ?>
                <?php
                if(! empty($category['sCategoryGroup']) and $current_group != $category['sCategoryGroup']) {
                    echo '<optgroup label="' . hs_htmlspecialchars($category['sCategoryGroup']) . '">';
                }
                ?>
                <option value="<?php echo $category['xCategory'] ?>"
                        <?php if($this->request_xCategory == $category['xCategory']) echo 'selected' ?>>
                    <?php echo $category['sCategory'] ?>
                </option>
                <?php
                if (!empty($current_group) and $current_group != $category['sCategoryGroup'] and empty($category['sCategoryGroup'])) {
                        echo '</optgroup>';

                    } //close if prev had been in another group
                $current_group = $category['sCategoryGroup'];
                ?>
            <?php endforeach; ?>
        </p>
        delete this entire line --> */ ?>

		<?php endif; ?>

		<?php //Output public custom fields. Required custom fields do not need to be specified in the "required" hidden field above. They are
		// automatically checked
		?>
		<?php foreach ($this->splugin('CustomFields', 'getPublicCustomFields') as $field) : ?>
			<?php $requiredClass = $field['isRequired'] ? ' required' : ''; //Determine if field is required. If so set style class
				?>
			<?php $fieldID = 'Custom' . $field['fieldID']; //Set the field ID for use below
				?>
			<?php $visible = $field['isAlwaysVisible'] ? '' : 'display:none;'; //Set if the custom field is visible by default
				?>

			<div id="<?php echo $fieldID ?>_wrapper" style="<?php echo $visible ?>"><label for="<?php echo $fieldID ?>" class="datalabel<?php echo $requiredClass ?>"><?php echo $field['fieldName'] ?></label><br />
				<?php echo $this->helper->showError($fieldID, '<br />') ?>

				<?php if ($field['fieldType'] == 'select') : ?>
					<select name="<?php echo $fieldID ?>">
						<option value=""></option>
						<?php foreach ($field['listItems'] as $item) : ?>
							<option value="<?php echo $item ?>" <?php if ($this->$fieldID == $item) {
																				echo 'selected';
																			} ?>><?php echo $item ?></option>
						<?php endforeach; ?>
					</select>
				<?php elseif ($field['fieldType'] == 'text') : ?>
					<?php
					$size = ($field['sTxtSize'] > 40)
						? 40 // If field text length is greater than 40, use 40
						: $field['sTxtSize']; // If field length is less than 40, use that length
					?>
					<input name="<?php echo $fieldID ?>" type="text" size="<?php echo $size ?>" maxlength="<?php echo $field['sTxtSize']; ?>" value="<?php echo $this->$fieldID ?>">
				<?php elseif ($field['fieldType'] == 'lrgtext') : ?>
					<textarea name="<?php echo $fieldID ?>" rows="<?php echo $field['lrgTextRows'] ?>" style="width:100%;"><?php echo $this->$fieldID ?></textarea>
				<?php elseif ($field['fieldType'] == 'checkbox') : ?>
					<input name="<?php echo $fieldID ?>" type="checkbox" value="1" <?php if ($this->$fieldID == 1) {
																								echo 'checked';
																							} ?>>
				<?php elseif ($field['fieldType'] == 'numtext') : ?>
					<input name="<?php echo $fieldID ?>" type="text" size="10" maxlength="10" value="<?php echo $this->$fieldID ?>">
				<?php elseif ($field['fieldType'] == 'drilldown') : ?>
					<?php echo $this->helper->getDrillDownField($field, ' '); ?>
				<?php elseif ($field['fieldType'] == 'decimal') : ?>
					<input name="<?php echo $fieldID ?>" type="text" size="10" maxlength="10" value="<?php echo $this->$fieldID ?>">
				<?php elseif ($field['fieldType'] == 'regex') : ?>
					<?php echo $this->helper->getRegexField($field); ?>
				<?php elseif ($field['fieldType'] == 'date') : ?>
					<?php echo $this->helper->getDateField($field); ?>
				<?php elseif ($field['fieldType'] == 'datetime') : ?>
					<?php echo $this->helper->getDateTimeField($field); ?>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<?php //portalFormFormat 1 is the complex 3 question display. 2 is the simple single textarea.
		//1 is the default. If you'd like to just use the simple textarea you can switch the setting in Admin->Settings->Portal

		if ($this->hd_portalFormFormat == 1) : ?>

			<p><label for="did" class="datalabel"><?php echo lg_portal_req_did ?></label><br />
				<?php echo $this->helper->showError('did', '<br />') ?>
				<textarea id="did" name="did" cols="50" rows="5" style="width:100%;"><?php echo $this->request_did ?></textarea>
			</p>

			<p><label for="expected" class="datalabel"><?php echo lg_portal_req_expected ?></label><br />
				<?php echo $this->helper->showError('expected', '<br />') ?>
				<textarea id="expected" name=" expected" cols="50" rows="5" style="width:100%;"><?php echo $this->request_expected ?></textarea>
			</p>

			<p><label for="actual" class="datalabel"><?php echo lg_portal_req_actual ?></label><br />
				<?php echo $this->helper->showError('actual', '<br />') ?>
				<textarea id="actual" name="actual" cols="50" rows="5" style="width:100%;"><?php echo $this->request_actual ?></textarea>
			</p>

		<?php elseif ($this->hd_portalFormFormat == 0) : ?>

			<p><label for="simple" class="datalabel"><?php echo lg_portal_req_simple ?></label><br />
				<?php echo $this->helper->showError('simple', '<br />') ?>
				<textarea id="simple" name="simple" cols="50" rows="10" style="width:100%;"><?php echo $this->request_simple ?></textarea>
			</p>

		<?php endif; ?>

		<?php //File uploads. You can turn this on and off via a setting in Admin->Settings->Portal. It's disabled by default
		?>
		<?php if ($this->hd_allowFileAttachments == 1) : ?>

			<p><label for="doc[]" class="datalabel"><?php echo lg_portal_req_file_upload ?></label><br />
				<?php //TIP: You can have multiple file uploads by adding more lines identical to the one below.
					?>
				<input type="file" id="doc[]" name="doc[]" size="40">
			</p>

		<?php endif; ?>

		<?php
		//Captcha form protection. You can turn this on and off via a setting in Admin->Settings->System Security. It's enabled by default
		//This text captcha should be sufficient for most automated spam. As of version 2.6 reCaptcha (http://recaptcha.net) is also supported for increased security.
		?>
		<?php include $this->loadTemplate('captcha.tpl.php'); ?>

	</div>

	<div class="formbuttondiv">
		<input type="submit" name="submit" value="<?php echo lg_portal_req_submitrequest ?>" />
	</div>

	<!-- START: SPAM Protection DO NOT REMOVE -->
	<?php echo $this->helper->getSPAMCheckFields() ?>
	<!-- END: SPAM Protection DO NOT REMOVE -->

</form>

<?php include $this->loadTemplate('footer.tpl.php'); ?>
