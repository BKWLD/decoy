<?// This view is rendered by the HTML::autocomplete() macro ?>

<div class="control-group datalist" data-js-view="datalist" data-route="<?=$route?>">
	<label for="<?=$id?>"><?=$label?></label>
	<div class="input-append">
		<input class="span5 autocomplete" type="text" placeholder="Search" id="<?=$id?>" value="<?=$old_title?>" autocomplete="off"/> <?// Displayed to users?>
	  <input type="hidden" name="<?=$id?>" value="<?=$old?>"/> <?// Submitted with POST ?>
	  
	  <?// If on an edit view with old data, start off in a "good" state?>
	  <? if ($old): ?>
	  	<span class="add-on match"><i class="icon-ok icon-white"></i></span>
	  
	  <?// Otherwise start in an un-matching state?>
		<? else: ?>
			<span class="add-on"><i class="icon-ban-circle"></i></span>
		<? endif ?>
	  
	</div>
</div>