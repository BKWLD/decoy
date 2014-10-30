-if(isset($sidebar) && !$sidebar->isEmpty())
	- # Open a related, vertical form, including it's columns
	!='<div class="row"><div class="col-md-7 related-left-col">'
	!=Former::vertical_open_for_files()
	
-else
	-# Open a horizontal, non-sidebar form
	!=Former::horizontal_open_for_files()