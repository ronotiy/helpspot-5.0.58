<div id="leftSidebar">

	<ul class="navBar">
		<li><a href="index.php" class="home <?php echo $this->helper->ns('home') ?>"><?php echo lg_portal_home ?></a></li>
		<li><a href="index.php?pg=request" class="request <?php echo $this->helper->ns('request') ?>"><?php echo lg_portal_submitrequest ?></a></li>
        <li><a href="index.php?pg=request.check" class="check <?php echo $this->helper->ns('check') ?>"><?php echo lg_portal_checkrequest ?></a></li>
	</ul>
	
	<?php if ($this->splugin('KB_Books', 'count')): ?>
		<ul class="navBar">
			<li><a href="index.php?pg=kb" class="books <?php echo $this->helper->ns('kb') ?>"><?php echo lg_portal_kb ?></a></li>
				<ul class="subnavBar">
					<?php foreach ($this->splugin('KB_Books', 'getBooks') as $book): ?>
					<li><a href="index.php?pg=kb.book&id=<?php echo $book['xBook'] ?>" class="book <?php echo $this->helper->ns('kb'.$book['xBook']) ?>"><?php echo $book['sBookName'] ?></a></li>
					<?php endforeach; ?>
				</ul>
			</li>
		</ul>
	<?php endif; ?>

	<?php if ($this->hd_phone): ?>
		<ul class="phonenavBar">
			<li><?php echo lg_portal_phonesupport ?></li>
			<ul class="subnavBar">
				<li><span class="phoneNum"><?php echo $this->hd_phone ?></span></li>
			</ul>
		</ul>
	<?php endif; ?>

</div>

<!-- Right side content container. This DIV is closed in the footer template -->
<div id="content2col">

<!-- Feedback box. Hidden by default and called from Javascript functions to provide user feedback -->
<div id="feedback_box" style="display:none;"></div>
