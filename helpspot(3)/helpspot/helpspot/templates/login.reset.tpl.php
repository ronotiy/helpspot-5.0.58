<?php
// todo: Only show this page if using internal auth


//Set page title
$this->assign('pg_title', lg_portal_login_forgot);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<h1><?php echo lg_portal_login_forgot_ex ?></h1>	<br />

<form action="<?php echo cHOST.'/index.php?pg=login.reset' ?>" method="post">
    <?php echo csrf_field(); ?>

    <?php if( session('status') ): ?>
        <p style="color:#0f901b;"> <?php echo session('status') ?> </p>
    <?php endif; ?>

    <p><label for="email" class="datalabel"><?php echo lg_portal_req_loginemail ?></label><br />
        <?php echo $this->helper->showError('email', '<br />') ?>
        <?php echo $this->helper->showError('token', '<br />') ?>
        <input type="text" name="email" id="email" size="40" maxlength="100" value="<?php echo old('email') ?? $_GET['email'] ?>" autocomplete="off" /><br />
    </p>

    <p><label for="password" class="datalabel"><?php echo lg_portal_req_loginpassword ?></label><br />
        <?php echo $this->helper->showError('password', '<br />') ?>
        <input type="password" name="password" id="password" size="40" maxlength="100" value="" autocomplete="off" />
    </p>

    <p><label for="password_confirmation" class="datalabel"><?php echo lg_portal_req_loginpassword_confirm ?></label><br />
        <input type="password" name="password_confirmation" id="password_confirmation" size="40" maxlength="100" value="" autocomplete="off" />
    </p>

    <p>
        <input type="hidden" name="token" value="<?php echo $_GET['token'] ?>">
        <input type="submit" name="submit" value="<?php echo lg_portal_req_pw_reset_link ?>" />
    </p>
</form>

<?php include $this->loadTemplate('footer.tpl.php'); ?>
