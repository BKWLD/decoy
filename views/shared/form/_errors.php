<?// Display form wide errors?>

<? if (!empty($errors) && !empty($errors->messages)): ?>
	<div class="alert alert-error">
	  <button type="button" class="close" data-dismiss="alert">×</button>
	  <strong>Validation Error!</strong>
	  
	  <?// Special duplicate slug method?>
	  <? if (in_array('slug', array_keys($errors->messages))): ?>
	  	A unique slug could not be formed from the name or title.  You must use a different value.
	  	
	  <?// Generic error message?>
	  <? else: ?>
	  	<? if (count($errors->messages) > 1): ?>
	  		The fields in conflict are highlighted below.
		  <? else: ?>
	  		The field in conflict is highlighted below.
		  <? endif ?>
		<? endif ?>
	</div>
<? endif ?>