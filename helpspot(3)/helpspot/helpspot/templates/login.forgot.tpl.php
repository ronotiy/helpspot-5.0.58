<?php
// todo: Only show this page if using internal auth


//Set page title
$this->assign('pg_title', lg_portal_login_forgot);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<h1><?php echo lg_portal_login_forgot_ex ?></h1>	<br />

<form action="<?php echo route('portal.password.email') ?>" method="post">
    <?php echo csrf_field(); ?>

    <?php if( session('status') ): ?>
        <p style="color:#0f901b;"> <?php echo session('status') ?> </p>
    <?php endif; ?>

    <p><label for="email" class="datalabel"><?php echo lg_portal_req_loginemail ?></label><br />
        <?php echo $this->helper->showError('email', '<br />') ?>
        <input type="text" name="email" id="email" size="40" maxlength="100" value="<?php old('email') ?>" autocomplete="off" /><br />
    </p>

    <p>
        <input type="submit" name="submit" value="<?php echo lg_portal_req_pw_reset_link ?>" />
    </p>
</form>

<?php include $this->loadTemplate('footer.tpl.php'); ?>
