!= View::make('decoy::shared.form._header', $__data)

%fieldset
	.legend=empty($item)?'New':'Edit'
	!= Former::text('from')->blockHelp('A URL path, beginning after "'.Request::root().'/". This can contain wildcards in the form of a <code>%</code>.  For instance, to match all URLs beginning with "blog/", use <code>blog/%</code>. For more complex matches, you may also use <a href="https://dev.mysql.com/doc/refman/5.1/en/regexp.html#operator_regexp" target="_blank">mysql regular expressions</a> like <code>^blog/.+$</code>.')
	!= Former::text('to')->blockHelp('An absolute path ( <code>/insight/example</code> ) or url ( <code>http://domain.com/path?id=num</code> ).')
	!= Former::radios('code')->radios(Bkwld\Library\Laravel\Former::radioArray(Bkwld\Decoy\Models\RedirectRule::$codes))->blockHelp('How should browsers treat this redirect.');
	!= Former::text('label')->help('An optional internal label used to identify this <b>Rule</b> in the Admin.')

!= View::make('decoy::shared.form._footer', $__data)