<?

/**
 * This is implemented by the _pagination partial and is based on 
 * /laravel/framework/src/Illuminate/Pagination/views/slider.php
 * The paginator is only shown if there are more results than the smallest
 * per_page option.
 */

// Figure out what controller is handling the request
$controller = app('decoy.wildcard')->detectController();
$per_page = $controller::$per_page;

$presenter = new Illuminate\Pagination\BootstrapPresenter($paginator); 
if ($paginator->getTotal() > $per_page): ?>
	<div class="pagination-wrapper">
	
		<?// The list of pages ?>
		<? if ($paginator->getLastPage() > 1): ?>
			<ul class="pagination pagination-desktop">
				<?=$presenter->render(); ?>
			</ul>
			<? // on mobile, just show first, prev, current, next, last pagination buttons ?>
			<ul class="pagination pagination-mobile">
				<?=str_replace('>1<', '>&laquo;<', $presenter->getLink(1)); ?>
				<?=$presenter->getPrevious('&lsaquo;'); ?>
				<? // to display the current active page ?>
				<?=$presenter->getPageRange($paginator->getCurrentPage(),$paginator->getCurrentPage()); ?>
				<?=$presenter->getNext('&rsaquo;'); ?>
				<?=str_replace('>'.$paginator->getLastPage().'<', '>&raquo;<', $presenter->getLink($paginator->getLastPage())); ?>
			</ul>
		<? endif ?>
	
		<?// Per page selector
		$options = array(
			$per_page, 
			$per_page * 2, 
			'all');
		$count = Input::get('count', $options[0]); ?>
		<ul class="per-page pagination">
			<li class="disabled"><span>Show</span></li>
			<? foreach($options as $option): ?>
				<? if ($count == $option): ?>
					<?=$presenter->getActivePageWrapper(ucfirst($option))?>
				<? else: ?>
					<?=$presenter->getPageLinkWrapper($paginator->addQuery('count', $option)->getUrl(1), ucfirst($option))?>
				<? endif ?>
			<? endforeach ?>
	
		</ul>
	</div>
<? endif ?>
