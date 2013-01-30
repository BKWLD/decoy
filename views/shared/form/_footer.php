<? 

/*
This partial is used to generate all of the HTML and layout that comes
after most CRUD forms.  It is used in conjunction with _form_header.
It expects:

	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- item (optional) : The data that is being edited
	
	- parent_id (optional) : The id of the parent row of the model
    that is the parent of what is being edited.  If news has mas 
    many photos and this form is for photos, it is the id of the
    associated news article

*/

?>

	<hr/>
	<div class="controls actions">
		<button type="submit" class="btn btn-success save"><i class="icon-file icon-white"></i> Save</button>
		
		<? if (!empty($item)): ?>
			<a class="btn btn-danger delete" href="<?=route($controller.'@delete', array($item->id))?>">
				<i class="icon-trash icon-white"></i> Delete
			</a>
		<? endif ?>
		
		<a class="btn back" href="<?=route('decoy::back')?>">Back</a>
	</div>

<?= Former::close() ?>
