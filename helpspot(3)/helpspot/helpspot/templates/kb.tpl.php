<?php
//Set page title
$this->assign('pg_title', lg_portal_kb);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>
	
<h1><?php echo lg_portal_kb ?></h1>	<br />
	
<?php foreach ($this->splugin('KB_Books', 'getBooks') as $book): ?>
<div class="<?php echo $this->helper->altrow('rowOn', 'rowOff') ?>">
	<a href="index.php?pg=kb.book&id=<?php echo $book['xBook'] ?>"><?php echo $book['sBookName'] ?></a>
	<?php echo nl2br($book['tDescription']) ?>
</div>
<?php endforeach; ?>

<?php include $this->loadTemplate('footer.tpl.php'); ?>