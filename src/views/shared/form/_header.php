<? 

/*
This partial is used to generate all of the HTML and layout that comes
before most CRUD forms.  It is used in conjunction with _form_footer.
It expects:

	- title: The title of this page
	
	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- item (optional) : The data that is being edited
	  
	- description (optiona) : A description for the view
	
*/

?>

<?// Page title ?>
<?/*
<div class-"page-header form-header">
	<h1><?=$title?>
		<? if(!empty($item) && app('decoy.auth')->can('create', $controller)): ?>
			<div class="btn-toolbar pull-right">
				<div class="btn-group">
					<a href="<?=URL::to(DecoyURL::relative('create'))?>" class="btn btn-info new"><i class="glyphicon glyphicon-plus"></i> New</a>
				</div>
			</div>
		<? endif ?>
		<? if (!empty($description)):?>
			<small><?=$description?></small>
		<? endif ?>
	</h1>
</div>
*/?>

<?// Show validation errors?>
<?=View::make('decoy::shared.form._errors')?>

<?// Form tag ?>
<?= Former::horizontal_open_for_files() ?>
	<?= Form::token() ?>
