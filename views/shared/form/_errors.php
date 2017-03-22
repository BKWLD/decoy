<?php // Display form wide errors?>
<?php if ($errors->any()): ?>

	<div class="alert alert-danger" role="alert">
	  <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
	  <strong>Validation Error!</strong>

	  <?php // Special duplicate slug method?>
	  <?php if ($errors->has('slug')): ?>
	  	A unique slug could not be formed from the name or title.  You must use a different value.

	  <?php // Generic error message?>
	  <?php else: ?>
	  	<?php if ($errors->count() > 1): ?>
	  		The fields in conflict are highlighted below.
		  <?php else: ?>
	  		The field in conflict is highlighted below.
		  <?php endif ?>
		<?php endif ?>
	</div>
<?php endif ?>
