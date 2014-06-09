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
	<div class="pagination">
	
		<?// The list of pages ?>
		<? if ($paginator->getLastPage() > 1): ?>
			<ul>
				<?=$presenter->render(); ?>
			</ul>
		<? endif ?>
	
		<?// Per page selector
		$options = array(
			$per_page, 
			$per_page * 2, 
			'all');
		$count = Input::get('count', $options[0]); ?>
		<ul class="per-page">
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
