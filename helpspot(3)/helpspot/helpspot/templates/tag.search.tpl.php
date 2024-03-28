<?php
//Set page title
$this->assign('pg_title', lg_portal_tagsearch.': '.$this->splugin('Tags', 'getTagName', $this->get_id));

include $this->loadTemplate('header.tpl.php');
include $this->loadTemplate('navigation.tpl.php');
?>

<h1 class="tag-header"><?php echo lg_portal_tagsearch.': '.$this->splugin('Tags', 'getTagName', $this->get_id); ?></h1>

<table width="100%" cellspacing="0">
<tr valign="top">
	<?php if ($this->splugin('KB_Books', 'count')): ?>
		<td style="padding-right:15px;width:50%;">
		
			<h3><?php echo lg_portal_tagsearch_books ?></h3>
			<table width="" cellspacing="0" class="tag-table"> 
			<?php foreach ($this->splugin('Tags', 'searchKBTags', $this->get_id) as $page): ?>
			<tr class="<?php echo $this->helper->altrow('rowOn', 'rowOff') ?>">
				<td>
					<a href="index.php?pg=kb.page&id=<?php echo $page['xPage'] ?>"><?php echo $page['sPageName'] ?></a> 
					<br /><?php echo $page['sBookName'] ?>
				</td>
			</tr>
			<?php endforeach; ?>
			</table>	
		
		</td>
	<?php endif; ?>
	<?php $this->helper->reset_altrow(); ?>
</tr>
</table>

<?php include $this->loadTemplate('footer.tpl.php'); ?>