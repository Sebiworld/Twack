<?php
namespace ProcessWire;

?>

<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php
	if ($this->metatags) {
		foreach ($this->metatags->getArray() as $meta) {
			echo $meta." \n";
		}
	}
	?>

	<link rel="shortcut icon" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon.ico" />
	<link rel="icon" type="image/x-icon" sizes="16x16 32x32" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon.ico">
	<link rel="apple-touch-icon" sizes="152x152" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-152-precomposed.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-144-precomposed.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-120-precomposed.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-114-precomposed.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-180-precomposed.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-72-precomposed.png">
	<link rel="apple-touch-icon" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-57.png">
	<link rel="icon" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-32.png" sizes="32x32">

	<!-- For IE10 Metro -->
	<meta name="msapplication-TileColor" content="#FFFFFF">
	<meta name="msapplication-TileImage" content="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-144.png">
	<meta name="theme-color" content="#ffffff">

	<!-- Chrome for Android -->
	<link rel="manifest" href="manifest.json">
	<link rel="icon" sizes="192x192" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-192.png">

	<?php
	// All CSS styles are added to the component using the addStyle() method:
	foreach (wire('config')->styles as $stylefile) {
		echo "\n\t<link rel='stylesheet' href='$stylefile' /> ";
	}
	?>
</head>
<body class="t-<?= $this->page->template->name; ?>">
	<?= $this->component->getGlobalComponent('header'); ?>
	<a href="#top" class="back-to-top btn btn-outline-dark">
		<i class="icon ion-ios-arrow-round-up"></i>
	</a>
	<div class="main-content" id="top">
		<?= $this->component->getGlobalComponent('dev_echo'); ?>

		<?php
		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				echo $component;
			}
		}
		?>
	</div>

	<?= $this->component->getInlineStyles(); ?>

	<?php
	// All scripts are added to the component using the addScript() method:
	foreach (wire('config')->scripts as $file) {
		echo "\n\t<script type='text/javascript' src='$file'></script>";
	}
	?>
</body>
</html>