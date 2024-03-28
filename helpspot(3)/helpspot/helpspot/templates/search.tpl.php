<?php
//Set page title
$this->assign('pg_title', lg_portal_search.': '.$this->get_q);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>


<p>
	<a href="index.php"><?php echo lg_portal_home ?></a>  &#8594;
	<b><?php echo lg_portal_search ?>: <?php echo $this->get_q ?></b>
</p>

<h1><?php echo lg_portal_search ?>: <?php echo $this->get_q ?> (<?php echo $this->splugin('Search', 'count', $_GET['q'], $this->get_area) ?>)</h1>	<br />

<?php include $this->loadTemplate('searchbox.tpl.php'); ?>

<?php if ($this->splugin('Tags', 'searchCount', $_GET['q'])): ?>
	<fieldset class="fieldset">
		<legend><b><?php echo lg_portal_searchtags ?></b></legend>
		<div class="tag-block tag-block-page">
			<?php foreach ($this->splugin('Tags', 'searchTags', $this->get_q) as $tags): ?>
				<a href="index.php?pg=tag.search&id=<?php echo $tags['xTag'] ?>">
					<?php echo $tags['sTag'] ?>
				</a> <span class="tag-sep">&nbsp;/&nbsp;</span>
			<?php endforeach; ?>
		</div>
	</fieldset>
<br />
<?php endif; ?>

<table width="100%" cellspacing="0">
<?php foreach ($this->splugin('Search', 'search', $_GET['q'], $this->get_area) as $result): ?>
<tr class="<?php echo $this->helper->altrow('rowOn', 'rowOff') ?>">
	<td>
		<a href="index.php<?php echo $result['link'] ?>"><?php echo $result['title'] ?></a> <br /> <?php echo $result['desc'] ?>
	</td>
	<td class="score">
		<?php echo $result['score'] ?>
	</td>
</tr>
<?php endforeach; ?>
</table>

<?php include $this->loadTemplate('footer.tpl.php'); ?>