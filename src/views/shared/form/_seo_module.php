<?// Adds fields for SEO ?>

<legend>SEO</legend>

<? 
	$span = empty($related) ? 'span9' : 'span6';
	echo Former::textarea('seo_description', 'Description')->class($span)->blockHelp('A couple of sentences that may be displayed within search engine results');
	echo Former::text('seo_keywords', 'Keywords')->class($span)->blockHelp('A comma delimited list of keywords that are used by some search engines');
?>