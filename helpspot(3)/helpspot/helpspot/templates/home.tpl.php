<?php
//Set page title
$this->assign('pg_title', $this->hd_name);

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>



<p><?php echo nl2br($this->hd_portalHomepageMsg) ?></p>

<?php if ($this->splugin('KB_HighlightedPages', 'count') > 0): //Only show highlighted pages if there are any?>
	<table width="555" cellspacing="0" class="forumtable">
	<tr>
		<td>
			<h2><?php echo lg_portal_highlightedpages ?></h2>	<br />
		</td>
	</tr> 
	<?php foreach ($this->splugin('KB_HighlightedPages', 'getHighlightedPages') as $page): ?>
	<tr class="<?php echo $this->helper->altrow('rowOn', 'rowOff') ?>">
		<td>
			<a href="index.php?pg=kb.page&id=<?php echo $page['xPage'] ?>">
				<?php echo $page['sBookName'] ?> ~ <?php echo $page['sPageName'] ?></a> 
		</td>
	</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>
<?php $this->helper->reset_altrow(); ?>

<?php if ($this->splugin('Tags', 'count') > 0): //Only show tags if there are any?>
	<table width="555" cellspacing="0" class="forumtable tag-cloud-homepage">
	<tr>
		<td>
			<h2><?php echo lg_portal_tags ?></h2>	<br />
		</td>
	</tr>
	<tr>
		<td class="tag-cloud-td">
			<div class="tag-block tag-block-home">
			<?php foreach ($this->splugin('Tags', 'getCloud') as $tag): ?>
				<a href="index.php?pg=tag.search&id=<?php echo $tag['xTag'] ?>"  style="font-size:<?php echo $tag['font-size'] ?>%;"><?php echo $tag['sTag'] ?></a> <span class="tag-sep">&nbsp;/&nbsp;</span> 
			<?php endforeach; ?>
			</div>
		</td>
	</tr>
	</table>
<?php endif; ?>

<?php $this->helper->reset_altrow(); ?>

<?php if ($this->splugin('KB_HelpfulPages', 'count') > 0): //Only show most helpful pages if there are any?>
	<table width="555" cellspacing="0" class="forumtable">
	<tr>
		<td>
			<h2><?php echo lg_portal_helpfulpages ?></h2>	<br />
		</td>
	</tr>
	<?php foreach ($this->splugin('KB_HelpfulPages', 'getHelpfulPages', 10) as $page): ?>
	<tr class="<?php echo $this->helper->altrow('rowOn', 'rowOff') ?>">
		<td>
			<a href="index.php?pg=kb.page&id=<?php echo $page['xPage'] ?>">
				<?php echo $page['sBookName'] ?> ~ <?php echo $page['sPageName'] ?></a> 
		</td>
	</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

<?php include $this->loadTemplate('footer.tpl.php'); ?>