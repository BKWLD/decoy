#elements-tests
	
	.standard-block(data-decoy-el='homepage.body.body') !=Decoy::el('homepage.body.body')

	.float-right(data-decoy-el='homepage.marquee.title' data-placement='left')!=Decoy::el('homepage.marquee.title')
	.clearfix

	%p(data-decoy-el='other.body.subtitle')!=Decoy::el('other.body.subtitle')

	%p
		%a(href=Decoy::el('other.body.link') data-decoy-el='other.body.link' data-placement='right') An example of a link

	%p
		%a(href=Decoy::el('other.body.pdf') data-decoy-el='other.body.pdf' data-placement='bottom') A downloadable file

	%img(src=Decoy::el('other.body.image') data-decoy-el='other.body.image' data-placement='bottom')
