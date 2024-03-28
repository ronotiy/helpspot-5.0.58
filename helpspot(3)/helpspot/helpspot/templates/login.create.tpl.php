<?php
// todo: Only show this page if using internal auth


//Set page title
$this->assign('pg_title', lg_portal_create_login);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<h1><?php echo lg_portal_create_login ?></h1>	<br />

<form action="<?php echo cHOST.'/index.php?pg=login.create' ?>" method="post">
    <?php echo csrf_field(); ?>
    <p><b><?php echo lg_portal_create_login_ex ?>:</b></p>

    <p><label for="email" class="datalabel"><?php echo lg_portal_req_loginemail ?></label><br />
        <?php echo $this->helper->showError('email', '<br />') ?>
        <input type="text" name="email" id="email" size="40" maxlength="100" value="<?php echo $this->get_login_email ?>" autocomplete="off" /><br />
    </p>

    <p><label for="password" class="datalabel"><?php echo lg_portal_req_loginpassword ?></label><br />
        <?php echo $this->helper->showError('password', '<br />') ?>
        <input type="password" name="password" id="password" size="40" maxlength="100" value="" autocomplete="off" />
    </p>

    <p><label for="password_confirmation" class="datalabel"><?php echo lg_portal_req_loginpassword_confirm ?></label><br />
        <input type="password" name="password_confirmation" id="password_confirmation" size="40" maxlength="100" value="" autocomplete="off" />
    </p>

    <p>
        <input type="submit" name="submit" value="<?php echo lg_portal_req_createbutton ?>" />
    </p>
</form>

<?php include $this->loadTemplate('footer.tpl.php'); ?>
