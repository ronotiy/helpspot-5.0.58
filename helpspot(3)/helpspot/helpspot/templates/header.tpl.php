<!DOCTYPE html>
<html lang="en">

<head>
	<!--meta data-->
	<?php echo $this->hd_CharSet ?>
	<!-- Charset meta tag taken from your language pack -->
	<meta name="keywords" content="" />
	<meta name="description" content="<?php echo $this->hd_name ?> - help desk and customer service portal" />
	<?php if ($this->get_page == 'request.check') : ?>
		<meta name="robots" content="noindex, nofollow">
	<?php else : ?>
		<meta name="robots" content="index, follow">
	<?php endif; ?>

	<title><?php echo $this->pg_title ?></title>

	<!--stylesheets-->
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cf_url ?>/index.php?pg=<?php echo $this->hd_theme ?>" media="screen, projection" />
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cf_url ?>/index.php?pg=<?php echo $this->hd_theme_ie ?>" />
<![endif]-->

	<!--ADMIN SETTING: Custom WYSIWYG styles-->
	<link rel="stylesheet" type="text/css" href="<?php echo $this->cf_url ?>/index.php?pg=kb.wysiwyg" media="screen, projection" />

	<!--javascript-->
	<script type="text/javascript">
		HS_CSRF_TOKEN = "<?php echo csrf_token(); ?>"
	</script>
	<script type="text/javascript" src="<?php echo $this->cf_url ?>/index.php?pg=js"></script>

</head>

<body onload="<?php echo $this->pg_onload ?>" class="page-<?php echo $this->get_page_css_class ?>">

	<!-- container div is closed in footer.tpl.php -->
	<div id="container">
		<h1 id="banner"><?php echo $this->hd_name ?></h1>
