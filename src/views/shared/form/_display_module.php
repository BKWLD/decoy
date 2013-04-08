<?
/**
 * Adds a slug and visibility field to the form.  It's broken up into sub paritals so
 * forms can use just the individual fields if they need to.
 */
?>

<?=render('decoy::shared.form.display._legend', $this->data())?>
<?=render('decoy::shared.form.display._slug', $this->data())?>
<?=render('decoy::shared.form.display._visible', $this->data())?>
