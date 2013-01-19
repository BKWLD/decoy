<?// The title that is pulled into a full width list ?>

<h1>
	<?=$title?> <span class="badge badge-inverse"><?=$count?></span>
	
	<div class="btn-toolbar pull-right">
			
		<?// Button to open the search form ?>
		<? if (!empty($search)): ?>
			<div class="btn-group animated-clear closed">
				<a class="btn search-toggle"><i class="icon-search"></i></a>
				<a class="btn search-clear js-tooltip" title="Reset search"><i class="icon-ban-circle"></i></a>
			</div>
		<? endif ?>
		
		<?// If we've declared this relationship a many to many one, show the autocomplete ?>
		<? if ($many_to_many): ?>
			<?=render('decoy::shared.form.relationships._many_to_many', $this->data())?>
		
		<?// Else it's a regular one to many, so show a link to create a new item ?>
		<? else: ?>
			<div class="btn-group">
				<a href="<?=HTML::new_route($controller, @$parent_id)?>" class="btn btn-info" ><i class="icon-plus icon-white"></i> New</a>
			</div>
		<? endif ?>

	</div>
	
	<?// Show description ?>
	<? if (!empty($description)):?>
		<small><?=$description?></small>
	<? endif ?>
</h1>

<?// Search form?>
<?=render('decoy::shared.list._search', $this->data())?>