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
	<a href="index.php?pg=kb.book&id=<?php echo $this->book['xBook'] ?>"><?php echo $this->book['sBookName'] ?></a>  &#8594;
	<b><?php echo lg_portal_kbprinter ?></b>
</p>

<h1><?php echo $this->book['sBookName'] ?></h1>

<div class="subheading"><?php echo nl2br($this->book['tDescription']) ?></div>

<ul class="kbtoc">
	<?php foreach ($this->splugin('KB_Tree', 'getChapters', $this->get_id) as $chapter): ?>
	<li><a href="#c<?php echo $chapter['xChapter'] ?>"><?php echo $chapter['name'] ?></a>
		<ul class="kbtocpage">
		<?php foreach ($this->splugin('KB_Tree', 'getPages', $chapter['xChapter']) as $page): ?>
			<li><a href="#p<?php echo $page['xPage'] ?>" class="<?php echo $page['class'] ?>"><?php echo $page['name'] ?></a></li>
		<?php endforeach; ?>
		</ul>
	</li>
	<?php endforeach; ?>
</ul>

<?php foreach ($this->splugin('KB_Tree', 'getChapters', $this->get_id) as $chapter): ?>
<a name="c<?php echo $chapter['xChapter'] ?>"></a>
<h1><?php echo $chapter['name'] ?></h1>
	<div style="margin-left:20px;">
		<?php foreach ($this->splugin('KB_Tree', 'getPages', $chapter['xChapter']) as $page): ?>
			<a name="p<?php echo $page['xPage'] ?>"></a>
			<h1><?php echo $page['name'] ?></h1>
				<p><?php echo $page['tPage'] ?></p>
		<?php endforeach; ?>
	</div>	
<?php endforeach; ?>

<?php include $this->loadTemplate('footer.tpl.php'); ?>