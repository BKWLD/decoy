#elements-tests
	
	.standard-block(data-decoy-el='homepage.body.body') !=Decoy::el('homepage.body.body')

	.float-right(data-decoy-el='homepage.marquee.title' data-placement='left')!=Decoy::el('homepage.marquee.title')
	.clearfix

	%img(src=Decoy::el('other.body.image') data-placement='bottom' data-decoy-el='other.body.image')
