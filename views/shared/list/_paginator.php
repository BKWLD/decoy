<?
/**
 * This is implemented by the _pagination partial and is based on
 * /laravel/framework/src/Illuminate/Pagination/views/slider.php
 * The paginator is only shown if there are more results than the smallest
 * per_page option.
 */

// Deps
use Bkwld\Decoy\Markup\PaginationPresenter;

// Figure out what controller is handling the request
$controller = app('decoy.wildcard')->detectController();
$per_page = $controller::$per_page;
if ($paginator->total() > $per_page): ?>
	<div class="pagination-wrapper">

		<?// The list of pages ?>
		<? if ($paginator->lastPage() > 1): ?>
			<span class="pagination-desktop">
				<?= with(new PaginationPresenter($paginator))->render(); ?>
			</span>

			<?// On mobile, just show first, prev, current, next, last pagination buttons ?>
			<span class="pagination-mobile">
				<?= with(new PaginationPresenter($paginator))->renderMobile(); ?>
			</span>
		<? endif ?>

		<?// Per page selector ?>
		<span class="per-page">
			<?= with(new PaginationPresenter($paginator))->renderPerPageOptions([
				$per_page,
				$per_page * 2,
				'all'
			]); ?>
		</span>
	</div>
<? endif ?>
