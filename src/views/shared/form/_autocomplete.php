<?// This view is rendered by the HTML::autocomplete() macro ?>

<div class="control-group autocomplete" data-js-view="autocomplete" data-route="<?=$route?>" data-allow-new="<?=$allow_new?1:0?>">
	<label for="<?=$id?>"><?=$label?></label>
	<div class="input-append">
		<input class="span5" type="text" placeholder="Search" id="<?=$id?>" value="<?=$old_title?>"/> <?// Displayed to users?>
	  <input type="hidden" name="<?=$id?>" value="<?=$old?>"/> <?// Submitted with POST ?>
	  
	  <?// If on an edit view with old data, start off in a "good" state?>
	  <? if ($old): ?>
	  	<span class="add-on btn-success"><i class="icon-ok icon-white"></i></span>
	  
	  <?// Otherwise start in an un-matching state?>
		<? else: ?>
			<span class="add-on"><i class="icon-ban-circle"></i></span>
		<? endif ?>
	  
	</div>
</div>