<?
/**
 * Adds a slug and visibility field to the form.  It's broken up into sub paritals so
 * forms can use just the individual fields if they need to.
 */
?>

<?=View::make('decoy::shared.form.display._legend', $__data)?>
<?=View::make('decoy::shared.form.display._slug', $__data)?>
<?=View::make('decoy::shared.form.display._visible', $__data)?>
