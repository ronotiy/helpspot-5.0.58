<?php if ($this->sPortalTerms != '' and $this->sPortalPrivacy != ''): // if we have both available?>
    <p>
        <input name="terms" id="terms" type="checkbox" value="1">
        <label for="terms" class="datalabel"><?php echo sprintf(lg_portal_agree_terms_privacy, $this->sPortalTerms, $this->sPortalPrivacy); ?></label><br>
        <?php echo $this->helper->showError('terms', '<br />') ?>
    </p>
<?php elseif ($this->sPortalTerms != '' and $this->sPortalPrivacy == ''): // if we only have terms?>
    <p>
        <input name="terms" id="terms" type="checkbox" value="1">
        <label for="terms" class="datalabel"><?php echo sprintf(lg_portal_agree_terms, $this->sPortalTerms); ?></label><br>
        <?php echo $this->helper->showError('terms', '<br />') ?>
    </p>
<?php elseif ($this->sPortalTerms == '' and $this->sPortalPrivacy != ''): // if we only have privacy?>
    <p>
        <input name="terms" id="terms" type="checkbox" value="1">
        <label for="terms" class="datalabel"><?php echo sprintf(lg_portal_agree_privacy, $this->sPortalPrivacy); ?></label><br>
        <?php echo $this->helper->showError('terms', '<br />') ?>
    </p>
<?php endif; ?>