<? 

/*
This partial is used to open forms that have a related data sidebar

	- title: The title of this page
	
	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- item (optional) : The data that is being edited
	  
	- description (optiona) : A description for the view


*/

?>

<h1 class="form-header related-form"><?=$title?>
	<? if(!empty($item) && app('decoy.auth')->can('create', $controller)): ?>
		<div class="btn-toolbar pull-right">
			<div class="btn-group">
				<a href="<?=URL::to(DecoyURL::relative('create'))?>" class="btn btn-info new"><i class="icon-plus icon-white"></i> New</a>
			</div>
		</div>
	<? endif ?>
	<? if (!empty($description)):?>
		<small><?=$description?></small>
	<? endif ?>
</h1>

<?// Show validation errors?>
<?=View::make('decoy::shared.form._errors')?>

<div class="row">
	<div class="col-md-6 related-left-col">
		<?= Former::vertical_open_for_files() ?>
			<?= Form::token() ?>