<p class="pagination">

	<?php if ($page->first_page() !== FALSE): ?>
		<a href="<?php echo HTML::chars($page->url($page->first_page())) ?>" rel="first"><?php echo __('First') ?></a>
	<?php else: ?>
		<?php echo __('First') ?>
	<?php endif ?>

	<?php if ($page->previous_page() !== FALSE): ?>
		<a href="<?php echo HTML::chars($page->url($page->previous_page())) ?>" rel="prev"><?php echo __('Previous') ?></a>
	<?php else: ?>
		<?php echo __('Previous') ?>
	<?php endif ?>

	<?php for ($i = 1; $i <= $page->total_pages(); $i++): ?>

		<?php if ($i == $page->current_page()): ?>
			<strong><?php echo $i ?></strong>
		<?php else: ?>
			<a href="<?php echo HTML::chars($page->url($i)) ?>"><?php echo $i ?></a>
		<?php endif ?>

	<?php endfor ?>

	<?php if ($page->next_page() !== FALSE): ?>
		<a href="<?php echo HTML::chars($page->url($page->next_page())) ?>" rel="next"><?php echo __('Next') ?></a>
	<?php else: ?>
		<?php echo __('Next') ?>
	<?php endif ?>

	<?php if ($page->last_page() !== FALSE): ?>
		<a href="<?php echo HTML::chars($page->url($page->last_page())) ?>" rel="last"><?php echo __('Last') ?></a>
	<?php else: ?>
		<?php echo __('Last') ?>
	<?php endif ?>

</p><!-- .pagination -->
