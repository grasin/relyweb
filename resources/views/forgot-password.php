<?php echo $header?>
	<div class="bheader bg">
		<h2><?=translate('Forgot password ?')?></h2>
	</div>
	<div class="col-md-4 account">
		<?php
			if (isset($error)){
				if ($error == false){
					echo '<div class="alert alert-success">'.translate('We have sent a reset link to your email !').'</div>';
				} else {
					echo '<div class="alert alert-warning">'.translate($error).'</div>';
				}
			}
		?>
		<form action="" method="post" class="form-horizontal single">
			<?=csrf_field() ?>
			<fieldset>
				<div class="form-group">
					<label class="control-label"><?=translate('E-mail') ?></label>
					<input name="email" type="email" value="<?=isset(request()->email) ? request()->email : '' ?>" class="form-control"  />
				</div>
				<input name="send" type="submit" value="<?=translate('Reset') ?>" class="btn btn-primary" />
			</fieldset>
		</form>
	</div>
	<div class="clearfix"></div>
<?php echo $footer?>