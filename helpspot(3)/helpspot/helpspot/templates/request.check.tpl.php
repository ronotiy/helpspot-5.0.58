<?php
//Set page title
$this->assign('pg_title', $this->get_id . ' : ' . lg_portal_checkrequest);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<?php if ($this->splugin('Request_Check', 'accessKeyIsValid', $this->get_id)) : ?>
	<h1><?php echo lg_portal_accessidheader ?> : <?php echo $this->get_id ?></h1>
	<?php include $this->loadTemplate('loginbar.tpl.php'); ?>
	<br />
	<?php echo lg_portal_accessnote ?>
	<br /><br />
<?php elseif ($this->requireAuth) : ?>
	<h1><?php echo lg_portal_loginrequired ?></h1> <br />
<?php else : ?>
	<h1><?php echo lg_portal_checkrequest ?></h1> <br />
<?php endif; ?>

<?php /* If an ID is availabe then show the details of the request, if not show the enter an ID form */ ?>
<?php if (!empty($this->get_id)) : ?>
	<form action="index.php?pg=request.check" method="post" enctype="multipart/form-data">
		<?php echo csrf_field(); ?>
		<input type="hidden" name="accesskey" value="<?php echo $this->get_id ?>" />

		<?php /* Make sure access key passed in get_id is in fact a valid request ID */ ?>
		<?php if ($this->splugin('Request_Check', 'accessKeyIsValid', $this->get_id)) : ?>

			<?php
					/* This code hides the
            request information after the request has been closed for X days. This keeps
            people from submitting information about a new request into an old one and also
            prevents search engines from getting this private information if the URL is accidentally
            published. You may change the time after the request is closed through the last variable below.
            Other valid examples are: '2 week', '1 month', '15 day'. Empty will turn off access as soon as the request is closed ''

            Leaving some time is also good because if the customers issue wasn't in fact solved then it gives them time to
            provide more information. In that case HelpSpot will reopen the request back to the original person assigned
            rather than the customer submitting a new request about an existing issue and the back and forth that results
            from that scenario.

            Note that as of version 2.4 this line also checks to see if the customer is logged in. If they are they are allowed
            to view the request even if the time specified below has passed.
        */ ?>
			<?php if ($this->splugin('Request_Check', 'isClosed', $this->get_id, '2 Day') && !$this->splugin('Request_Check', 'isLoggedIn')) : ?>

				<p><?php echo lg_portal_requestclosed ?></p>

				<p>
					<a href="index.php?pg=request.check"><?php echo lg_portal_closedlogin ?></a>
					<?php echo lg_portal_closedor ?>
					<a href="index.php?pg=request"><?php echo lg_portal_closedsubmitnew ?></a>
				</p>

			<?php else : ?>

				<?php //Get the details for this request
							?>
				<?php $request = $this->splugin('Request_Check', 'getRequestDetails', $this->get_id); ?>

				<?php
							/* Below are extra features you can optionally uncomment to enhance the request check page.

                <?php //Show the current status of this request ?>
                <b>Current Status:</b> <?php echo $request['sStatus'] ?><br />

                <?php //Show the assigned staff member if there is one ?>
                <b>Assigned To:</b>
                <?php if($request['xPersonAssignedTo']): ?>
                    <?php echo $request['assignedto_firstname'] ?> <?php echo $request['assignedto_lastname'] ?><br />
                <?php else: ?>
                    Currently unassigned<br />
                <?php endif; ?>

                <?php //Show the date the request was closed ?>
                <?php if($request['dtGMTClosed']): ?>
                    <b>Closed On:</b> <?php echo $this->helper->longDateFormat($request['dtGMTClosed']) ?><br />
                <?php endif; ?>

                <?php //Show the current category of this request ?>
                <b>Current Category:</b> <?php echo $request['sCategory'] ?><br />

                <?php //Output public custom fields. ?>
                <?php foreach($this->splugin('CustomFields','getPublicCustomFields') AS $field): ?>

                    <?php $fieldID = 'Custom'.$field['fieldID']; //Set the field ID for use below ?>
                    <?php $visible = $field['isAlwaysVisible'] ? '' : 'display:none;'; //Set if the custom field is visible by default ?>

                    <div id="<?php echo $fieldID ?>_wrapper" style="<?php echo $visible ?>">
                    <b><?php echo $field['fieldName'] ?>:</b>

                        <?php if($field['fieldType'] == 'checkbox'): ?>
                            <?php echo ($request[$fieldID] == 1 ? lg_portal_checkboxchecked : lg_portal_checkboxempty) ?>
                        <?php elseif($field['fieldType'] == 'drilldown'): ?>
                            <?php echo $this->helper->showDrillDownField($request[$fieldID]); ?>
                        <?php elseif($field['fieldType'] == 'date'): ?>
                            <?php echo $this->helper->shortDateFormat($request[$fieldID]) ?>
                        <?php elseif($field['fieldType'] == 'datetime'): ?>
                            <?php echo $this->helper->longDateFormat($request[$fieldID]) ?>
                        <?php else: ?>
                            <?php echo (empty($request[$fieldID]) ? ' - ' : $request[$fieldID]) ?>
                        <?php endif; ?>
                    </div>

                <?php endforeach; ?>
                <!-- You must uncomment this line if you want to show custom fields -->
                <script type="text/javascript" language="JavaScript">ShowCategoryCustomFields(<?php echo $request['xCategory'] ?>);</script>
                <br />
            */ ?>

				<?php foreach ($this->splugin('Request_Check', 'getPublicUpdates', $this->get_id) as $event) : ?>

					<div class="<?php echo $this->helper->altrow('rowOn', 'rowOff') ?> requestpad">
						<?php
										// by default we only show the staff members first name here for privacy. To show the full name add:  echo $event['lastname']
										?>
						<span class="namedate"><?php echo $event['firstname'] ?> <span style="font-weight:normal;">&nbsp; &middot; &nbsp; <?php echo $this->helper->longDateFormat($event['dtGMTChange']) ?></span></span>
						<br />
						<?php echo $event['tNote'] ?>

						<?php if (count($event['files'])) : ?>
							<br />
							<?php foreach ($event['files'] as $file) : ?>
								<?php echo $file ?><br />
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

				<?php endforeach; ?>

				<?php //Don't show update box for requests closed longer than 2 days
							?>
				<?php if (!$this->splugin('Request_Check', 'isClosed', $this->get_id, '2 Day')) : ?>

					<p><label for="update" class="datalabel"><?php echo lg_portal_req_update ?></label><br />
						<?php echo $this->helper->showError('update', '<br />') ?>
						<?php echo lg_portal_updatebox ?><br />
						<textarea name="update" cols="60" rows="10"><?php echo $this->request_update ?></textarea>
					</p>

					<?php //File uploads. You can turn this on and off via a setting in Admin->Settings->Portal. It's disabled by default
									?>
					<?php if ($this->hd_allowFileAttachments == 1) : ?>

						<p><label for="doc[]" class="datalabel"><?php echo lg_portal_req_file_upload ?></label><br />
							<?php //TIP: You can have multiple file uploads by adding more lines identical to the one below.
												?>
							<input type="file" name="doc[]" size="40">
						</p>

					<?php endif; ?>

					<div class="formbuttondiv">
						<input type="submit" name="submit" value="<?php echo lg_portal_req_updaterequest ?>" />
					</div>

				<?php endif; ?>

			<?php endif; ?>
		<?php else : ?>

			<p><b><?php echo lg_portal_invalidkey ?></b></p>

		<?php endif; ?>

	</form>

<?php else : ?>

	<form action="index.php?pg=request.check" method="get">
		<input type="hidden" name="pg" value="request.check" />
		<p><?php echo $this->helper->showError('id', '<br />') ?>
			<label for="id"><b><?php echo lg_portal_req_enterkey ?>:</b></label><br /><br />
			<input type="text" id="id" name="id" value="" size="20" maxlength="100" />
		</p>
        <p>
            <input type="submit" name="submit" id="accesskey-btn" value="<?php echo lg_portal_check ?>" />
        </p>
	</form>

	<hr width="80%" />

	<form action="index.php?pg=login" method="post">
		<?php echo csrf_field(); ?>
		<p><b><?php echo lg_portal_req_login ?>:</b></p>

		<p><label for="login_email" class="datalabel"><?php echo $this->hd_requestCheckAuthType == 'internal' ? lg_portal_req_loginemail : lg_portal_req_loginusername ?></label><br />
			<?php echo $this->helper->showError('login_email', '<br />') ?>
			<input type="text" name="login_email" id="login_email" size="40" maxlength="100" value="<?php echo $this->get_login_email ?>" autocomplete="off" /><br />
		</p>

		<p><label for="login_password" class="datalabel"><?php echo lg_portal_req_loginpassword ?></label><br />
			<input type="password" name="login_password" id="login_password" size="40" maxlength="100" value="" autocomplete="off" />
		</p>

		<p>
			<input type="submit" name="submit" value="<?php echo lg_portal_req_loginbutton ?>" />
			<?php if ($this->hd_requestCheckAuthType == 'internal') : ?>
				<span style="padding: 14px 0px; display: inline-block;"><a href="index.php?pg=login.forgot"><?php echo lg_portal_req_emailpassword ?>?</a></span>
			<?php endif; ?>
		</p>
	</form>
	<div style="text-align: center;">
		<hr width="80%">
		<div style="margin: 0 auto; padding: 10px;">
			<?php if ($this->hd_requestCheckAuthType == 'internal') : ?>
				<?php //only show this password retrieval link if we're using internal authentication on the portal
						?>
				<a href="index.php?pg=login.create"><?php echo lg_portal_req_logincreate ?></a>
			<?php endif; ?>
		</div>
	</div>

<?php endif; ?>

<?php if (isset($_GET['reset_password'])) : ?>
	<script type="text/javascript">
		document.observe("dom:loaded", function() {
			var page_href = location.href + " ";
			if (page_href.search(/password/i) > 0) {
				$("feedback_box").show();
				$("feedback_box").addClassName("feedback_box_positive");
				$("feedback_box").update("<?php echo htmlentities(lg_portal_password_reset) ?>");
			}
		});
	</script>
<?php endif; ?>

<?php include $this->loadTemplate('footer.tpl.php'); ?>
