<?php echo $header?>
	<div class="bheader bg">
		<h2><?=translate('Reset your password')?></h2>
	</div>
	<div class="col-md-4 account">
		<?php
			if (isset($error)){
				echo '<div class="alert alert-warning">'.translate($error).'</div>';
			}
		?>
		<form action="" method="post" class="form-horizontal single">
			<?=csrf_field() ?>
			<fieldset>
				<div class="form-group">
					<label class="control-label"><?=translate('New password') ?></label>
					<input name="password" type="password" class="form-control"  />
				</div>
				<input name="reset" type="submit" value="<?=translate('Reset') ?>" class="btn btn-primary" />
			</fieldset>
		</form>
	</div>
	<div class="clearfix"></div>
<?php echo $footer?>