<?// This view is rendered by the HTML::datalist() macro ?>

<div class="control-group datalist" data-js-view="datalist" data-controller-route="<?=$route?>">
	<label for="<?=$id?>"><?=$label?></label>
	<div class="input-append">
		<input class="span5 autocomplete" type="text" placeholder="Search" id="<?=$id?>" value="<?=$old_title?>" autocomplete="off"/> <?// Displayed to users?>
	  <input type="hidden" name="<?=$id?>" value="<?=$old?>"/> <?// Submitted with POST ?>
	  
	  <?// If on an edit view with old data, start off in a "good" state?>
	  <? if ($old): ?>
	  	<a class="add-on btn btn-info" href="<?=$route?>/<?=$old?>"><i class="icon-pencil icon-white"></i></a>
	  
	  <?// Otherwise start in an un-matching state?>
		<? else: ?>
			<a class="add-on"><i class="icon-ban-circle"></i></a>
		<? endif ?>
	  
	</div>
</div>