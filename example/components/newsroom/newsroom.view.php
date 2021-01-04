<?php
namespace ProcessWire;

?>
<div class="news-cards">
	<?php
	if ($this->count) {
		?>
		<i class="count"><?= $this->count; ?> Articles</i>
		<?php
	}
	?>

	<?php
	if ($this->childComponents && count($this->childComponents) > 0) {
		?>
		<div class="masonry-grid">
			<div class="masonry-grid-sizer"></div>
			<?php
			foreach ($this->childComponents as $newsitem) {
				?>
				<div class="masonry-grid-item">
					<?= $newsitem; ?>
				</div>
				<?php
			}
			?>
		</div>

		<?php
		if ($this->hasMore) {
			?>
			<div class="btn-group" role="group">
				<button type="button" class="btn btn-primary" data-aktion="loadMore" data-offset="<?= $this->lastElementIndex + 1; ?>">More...</button>
			</div>
			<?php
		}
	} else {
		?>
		<div class="masonry-grid">
			<div class="masonry-grid-sizer"></div>
		</div>

		<div class="alert alert-info  no-items" role="alert">
			<strong>No items found.</strong><br/>
		</div>
		<?php
	}
	?>
</div>