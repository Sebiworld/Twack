<?php
namespace ProcessWire;

?>
<div class="card newsitem-card" data-id="<?= $this->page->id; ?>">
	<?php
	if ($this->page->image) {
		?>
		<div class="aspectratio ar-2-1 card-img-top">
			<?php
			echo $this->component->getService('ImageService')->getImageTag(array(
				'image' => $this->page->image,
				'outputAs' => 'bg-image',
				'classes' => 'ar-content newsitem-image',
				'normal' => array(
					'height' => 300
				)
			));
			?>
		</div>
		<?php
	} else {
		?>
		<div class="aspectratio ar-2-1 card-img-top">
			<?php
			echo $this->component->getService('ImageService')->getPlaceholderImage(array(
				'outputAs' => 'bg-image',
				'classes' => 'ar-content newsitem-image',
				'normal' => array(
					'height' => 300
				)
			));
			?>
		</div>
		<?php
	}
	?>
	<div class="card-block">
		<div class="card-meta">
			published on <?= date('d.m.Y', $this->page->getUnformatted('date')); ?>
		</div>

		<h4 class="card-title"><?= $this->page->title; ?></h4>
		<p class="card-text">
			<?= $this->page->introduction; ?>
		</p>

		<a href="<?= $this->page->url; ?>" class="btn btn-light btn-inlinecolor hvr-grow">More...</a>
	</div>
</div>
