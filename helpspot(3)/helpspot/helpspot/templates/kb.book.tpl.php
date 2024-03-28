<?php
//Access book information
$this->book = $this->splugin('KB_Tree', 'getBook', $this->get_id);

//Security - $this->book returns false if get_id is not valid or is for a private book
if (! $this->book) {
    exit();
}

//Set page title
$this->assign('pg_title', $this->book['sBookName']);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<p>
	<a href="index.php"><?php echo lg_portal_home ?></a>  &#8594;
	<b><?php echo $this->book['sBookName'] ?></b>
</p>

<h1><?php echo $this->book['sBookName'] ?></h1>

<div class="subheading"><?php echo nl2br($this->book['tDescription']) ?></div>

<ul class="kbtoc">
	<?php foreach ($this->splugin('KB_Tree', 'getChapters', $this->get_id) as $chapter): ?>
	<li><a href="index.php?pg=kb.chapter&id=<?php echo $chapter['xChapter'] ?>"><?php echo $chapter['name'] ?></a>
		<ul class="kbtocpage">
		<?php foreach ($this->splugin('KB_Tree', 'getPages', $chapter['xChapter']) as $page): ?>
			<li><a href="index.php?pg=kb.page&id=<?php echo $page['xPage'] ?>" class="<?php echo $page['class'] ?>"><?php echo $page['name'] ?></a></li>
		<?php endforeach; ?>
		</ul>
	</li>
	<?php endforeach; ?>
</ul>

<p>
	<a href="index.php?pg=kb.printer.friendly&id=<?php echo $this->book['xBook'] ?>"><?php echo $this->book['sBookName'] ?>: <?php echo lg_portal_kbprinter ?></a>
</p>

<?php include $this->loadTemplate('footer.tpl.php'); ?>