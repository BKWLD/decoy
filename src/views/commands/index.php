<div id="commands">
	<? foreach($commands as $namespace => $subcommands): ?>
		<div class='col-sm-6 col-lg-4'>
			<div class="standard-list">
				<div class="legend sidebar-header"><?=$namespace?></div>
				<table>
					<? foreach($subcommands as $name => $command): ?>
						<tr data-js-view="task-method">
							<td>
								<a href="<?=route('decoy/commands@execute', $command->getName())?>" class="btn btn-default">Execute</a>
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
		</div>
	<? endforeach ?>
</div>