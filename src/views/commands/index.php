<h1>Commands
	<small>Trigger any command for this site.  Note: these may take awhile to execute.</small>
</h1>

<div id="commands">
	<? foreach($commands as $namespace => $subcommands): ?>
		<div class='span6'>
			<legend><?=$namespace?></legend>
			<table>
				<? foreach($subcommands as $name => $command): ?>
					<tr data-js-view="task-method">
						<td>
							<a href="<?=route('decoy\commands@execute', $command->getName())?>" class="btn">Execute</a>
						</td>
						<td>
							<p>
								<?=$name?>
								<img src="/packages/bkwld/decoy/img/spinners/46x46.gif"/>
							</p>
							<p><small><?=$command->getDescription()?></small></p>
						</td>
					</tr>
				 <? endforeach ?>
			</table>
		</div>
	<? endforeach ?>
</div>