<?php if ($this->hd_useCaptcha == 1): ?>

	<p><label for="captcha" class="datalabel required"><?php echo lg_portal_captcha ?> - </label><b class="captcha_label"><?php echo $this->helper->encodeText(session('portal_captcha')) ?></b><br />
		<?php echo $this->helper->showError('captcha', '<br />') ?>
		<input type="text" name="captcha" size="15" maxlength="250" value="" />
	</p>
	
<?php elseif ($this->hd_useCaptcha == 2): ?>
	<?php echo $this->helper->reCaptcha($this->hd_reCAPTCHALang); ?>
	<?php /* <p><label for="recaptcha_response_field" class="datalabel required"><?php echo lg_portal_recaptcha ?></label> - <a href="javascript:Recaptcha.reload();"><?php echo lg_portal_recaptcha_changewords ?></a><br />
        <?php echo $this->helper->showError('recaptcha','<br />') ?>
        <?php echo $this->helper->reCaptcha(); ?>
    </p> */?>
<?php endif; ?>
