-if(isset($sidebar) && !$sidebar->isEmpty())
	-# Open form and setup for columns
	!=Former::vertical_open_for_files()
	!='<div class="row"><div class="col-md-7 related-left-col">'
	
-else
	-# Open a horizontal, non-sidebar form
	!=Former::horizontal_open_for_files()