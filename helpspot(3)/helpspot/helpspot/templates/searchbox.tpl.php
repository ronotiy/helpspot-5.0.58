<?php if ($this->splugin('KB_Books', 'count')): ?>
	<form action="index.php" method="get">
		<!-- no CSRF field for GET requets -->
	<input type="hidden" name="pg" value="search">
	<div class="">
		<p align="center">
			<label for="q" style="display: none;">Search</label>
			<input type="text" name="q" id="q" value="<?php echo $this->get_q ?>">
			<input type="hidden" name="area" value="kb">
			<input type="submit" name="submit" value="<?php echo lg_portal_search ?>">
		</p>
	</div>
	</form>
<?php endif; ?>
