<? 

/*
This partial is used to generate all of the HTML and layout that comes
before most CRUD forms.  It is used in conjunction with _form_footer.
It expects:

	- title: The title of this page
	
	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- item (optional) : The data that is being edited
	
	- parent_id (optional) : The id of the parent row of the model
	  that is the parent of what is being edited.  If news has mas 
	  many photos and this form is for photos, it is the id of the
	  associated news article
	  
	- description (optiona) : A description for the view
	
	- no_legened (optional) : A boolean that, if true, hides the legend

*/

?>

<?// Page title ?>
<h1 class="form-header"><?=$title?>
	<? if(!empty($item)): ?>
		<div class="btn-toolbar pull-right">
			<div class="btn-group">
				<a href="<?=Html::relative($controller, @$parent_id)?>" class="btn btn-info new"><i class="icon-plus icon-white"></i> New</a>
			</div>
		</div>
	<? endif ?>
	<? if (!empty($description)):?>
		<small><?=$description?></small>
	<? endif ?>
</h1>

<?// Show validation errors?>
<?=View::make('decoy::shared.form._errors')?>

<?// The action that is currently being handled ?>
<? if (empty($no_legend)): ?>
	<legend><?=empty($item)?'New':'Edit'?></legend>
<? endif ?>

<?// Form tag ?>
<?= Former::horizontal_open_for_files() ?>
	<?= Form::token() ?>
