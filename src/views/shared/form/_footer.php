<? 

/*
This partial is used to generate all of the HTML and layout that comes
after most CRUD forms.  It is used in conjunction with _form_header.
It expects:

	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- item (optional) : The data that is being edited

*/

?>

	<hr/>
	<div class="controls actions">
		<div class="btn-group">
			<button name="_save" value="save" type="submit" class="btn btn-success save"><i class="icon-file icon-white"></i> Save</button>
			<button name="_save" value="back" type="submit" class="btn btn-success save_back">&amp; Back</button>
			<button name="_save" value="new" type="submit" class="btn btn-success save_new">&amp; New</button>
		</div>
		
		<? if (!empty($item)): ?>
			<a class="btn btn-danger delete" href="<?=route($controller.'@delete', array($item->id))?>">
				<i class="icon-trash icon-white"></i> Delete
			</a>
		<? endif ?>
		
		<a class="btn back" href="<?=\Decoy\Breadcrumbs::smart_back()?>">Back</a>
	</div>

<?= Former::close() ?>
