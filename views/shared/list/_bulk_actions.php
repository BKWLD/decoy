<!-- Bulk actions -->
<tr class="hide warning bulk-actions">
	<td colspan="999">
		<a class="btn btn-danger delete-selected" href="#">
			<span class="glyphicon glyphicon-trash"></span>
            <?php echo __('decoy::list.bulk_actions.delete_selected'); ?>
		</a>
	</td>
</tr>

<!-- Delete confirmation -->
<tr class="hide error fade in delete-alert">
	<td colspan="999">
		<span><?php echo __('decoy::list.bulk_actions.confirm_delete'); ?></span>
		<a class="btn btn-danger delete-confirm" href="#">
            <?php echo __('decoy::list.bulk_actions.yes_delete'); ?>
        </a>
		<a class="btn btn-default delete-cancel" href="#">
            <?php echo __('decoy::list.bulk_actions.cancel_delete'); ?>
        </a>
	</td>
</tr>
