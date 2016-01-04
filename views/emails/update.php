<p>
	<?=$editor_first_name?> <?=$editor_last_name?> has updated your account info on <?=$root?>. Your current account info is:<br/>
	<br/>
	<b>Name:</b> <?=$first_name?> <?=$last_name?><br/>
	<b>Email:</b> <?=$email?><br/>
	<? if ($password): ?><b>Password:</b> <?=$password?> (you should change this ASAP)
	<? else: ?><b>Password:</b> Unchanged (and cannot be displayed because it is one-way encrypted)
	<? endif ?>
</p>