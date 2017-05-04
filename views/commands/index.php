<div id="commands">
	<?php foreach($commands as $namespace => $subcommands): ?>
		<div class='col-sm-6 col-lg-4'>
			<div class="standard-list fieldset">
				<div class="legend sidebar-header"><?=$namespace?></div>
				<table>
					<?php foreach($subcommands as $name => $command): ?>
						<tr data-js-view="task-method">
							<td>
								<a data-action="<?=route('decoy::commands@execute', $command->getName())?>"
                                   class="btn btn-default"><?=__('decoy::commands.execute')?></a>
							</td>
							<td>
								<p>
									<?=$name?>
									<div class='spinner-46'></div>
								</p>
								<p><small><?=$command->getDescription()?></small></p>
							</td>
						</tr>
					 <?php endforeach ?>
				</table>
			</div>
		</div>
	<?php endforeach ?>
</div>
