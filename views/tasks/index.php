<h1>Tasks
	<small>Trigger any task for this site.  Note: these may take awhile to execute.</small>
</h1>

<div id="tasks">
	<? foreach($tasks as $task => $methods): ?>
		<div class='span6'>
			<legend><?=ucwords(str_replace('_',' ', $task))?></legend>
			<table>
				<? foreach($methods as $method): ?>
					<tr data-js-view="task-method">
						<td>
							<a href="<?=route('decoy::tasks@execute', array($task, $method))?>" class="btn">Execute</a>
						</td>
						<td>
							<?=ucwords(str_replace('_',' ', $method))?>
							<img src="/bundles/decoy/img/spinners/46x46.gif" />
						</td>
					</tr>
				 <? endforeach ?>
			</table>
		</div>
	<? endforeach ?>
</div>