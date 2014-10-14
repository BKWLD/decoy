<?// The title that is pulled into a full width list ?>

<div class="full-header">
	<h1>
		<?=$title?> <span class="badge"><?=$count?></span>
		
		<div class="btn-toolbar pull-right">
				
			<?// Button to open the search form ?>
			<? if (!empty($search)): ?>
				<div class="btn-group animated-clear closed search-controls">
					<a class="btn btn-default search-toggle"><span class="glyphicon glyphicon-search"></span></a>

					<?// Change the default container to fix a Chrome issue https://github.com/BKWLD/decoy/issues/239 ?>
					<a class="btn btn-default search-clear js-tooltip" data-container=".full-header .btn-toolbar" title="Reset search"><span class="glyphicon glyphicon-ban-circle"></span></a>
				</div>
			<? endif ?>
			
			<?// If we've declared this relationship a many to many one, show the autocomplete ?>
			<? if (!empty($many_to_many) && app('decoy.auth')->can('update', $controller)): ?>
				<?=View::make('decoy::shared.form.relationships._many_to_many', $__data)?>
			
			<?// Else it's a regular one to many, so show a link to create a new item ?>
			<? elseif (app('decoy.auth')->can('create', $controller)): ?>
				<div class="btn-group">
					<a href="<?=URL::to(DecoyURL::relative('create'))?>" class="btn btn-info new" ><span class="glyphicon glyphicon-plus"></span> New</a>
				</div>
			<? endif ?>

		</div>
		
		<?// Show description ?>
		<? if (!empty($description)):?>
			<small><?=$description?></small>
		<? endif ?>
	</h1>
</div>

<?// Search form?>
<?=View::make('decoy::shared.list._search', $__data)?>