<? 

/*
This partial is used to generate the HTML that ends the new/edit form and begins
the sidebar for related data on forms that have a related data sidebar.

	- controller : A string depicting the controller.  This is used in
		generating links.  I.e. 'admin.news'
		
	- item (optional) : The data that is being edited
	
	- parent_id (optional) : The id of the parent row of the model
    that is the parent of what is being edited.  If news has mas 
    many photos and this form is for photos, it is the id of the
    associated news article

*/

?>
			
		<?// Submit buttons?>
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
</div>

<?// Related data container?>
<div class="span5 offset1 related">