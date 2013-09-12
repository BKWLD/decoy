<?// Page title ?>
<h1 class="form-header">Fragments
	<small>Text, images, and files that don't make sense in a standard content managed list.</small>
</h1>

<?// Show validation errors?>
<?=View::make('decoy::shared.form._errors')?>

<?// Form tag ?>
<?= Former::vertical_open_for_files() ?>
	<?= Form::token() ?>

	<?// Create navigation ?>
	<div class="well">
		<ul class="nav nav-pills">
			<? foreach(array_keys($fragments) as $i => $title): ?>
				<li class="<?=$i===0?'active':null?>"><a href="#<?=Str::slug($title)?>" data-toggle="tab">
					<?=$title?></a>
				</li>
			<? endforeach ?>
		</ul>
	</div>
	
	<?// Create pages ?>
	<div class="tab-content">
		<? foreach($fragments as $title => $sections): ?>
			<div class="tab-pane masonry <?=$title==current(array_keys($fragments))?'active':null?>" id="<?=Str::slug($title)?>">
				
				<?// Create sections ?>
				<? foreach($sections as $title => $pairs): ?>
					<div class='span6'>
						<legend><?=$title?></legend>
						
						<?// Create pairs ?>
						<? foreach($pairs as $label => $value) {
							switch($value->type) {
								case 'text': 
									echo Former::text($value->key, $label)->class('span6'); 
									break;
								case 'textarea': 
									echo Former::textarea($value->key, $label)->class('span6'); 
									break;
								case 'wysiwyg':
									echo Former::textarea($value->key, $label)->class('wysiwyg');
									break;
								case 'image':
									echo Decoy::imageUpload($value->key, $label);
									break;
								case 'file':
									echo Decoy::fileUpload($value->key, $label);
									break;
							}
						} ?>
					</div>
				<? endforeach ?>
				
			</div>
		<? endforeach ?>
	</div>
	
	<hr/>
	<div class="controls actions">
		<button type="submit" class="btn btn-success save"><i class="icon-file icon-white"></i> Save all tabs</button>
	</div>

<?= Former::close() ?>
