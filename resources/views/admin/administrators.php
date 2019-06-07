<?php
	echo $header;
	if($action == "add")
	{
		echo notices().'<form action="" method="post" class="form-horizontal single">
				'.csrf_field().'
				<h5><a href="administrators"><i class="icon-arrow-left"></i></a>Add new Admin</h5>
					<fieldset>
						  <div class="form-group">
							<label class="control-label">Name</label>
							<input name="name" type="text"  class="form-control" required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Email</label>
							<input name="email" type="text" class="form-control"  required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Password</label>
							<input name="pass" type="password" class="form-control"  required/>
						  </div>
						  <input name="add" type="submit" value="Add Admin" class="btn btn-primary" />
					</fieldset>
				</form>';
	}
	elseif($action == "edit")
	{
		echo notices().'<form action="" method="post" class="form-horizontal single">
				'.csrf_field().'
				<h5><a href="administrators"><i class="icon-arrow-left"></i></a>Edit admin</h5>
					<fieldset>
						  <div class="form-group">
							<label class="control-label">Name</label>
							<input name="name" type="text"  value="'.$admin->name.'" class="form-control" required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Email</label>
							<input name="email" type="text"  value="'.$admin->email.'" class="form-control" required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Password</label>
							<input name="pass" type="password"  class="form-control" required/>
						  </div>
						  <input name="edit" type="submit" value="Edit admin" class="btn btn-primary" />
					</fieldset>
				</form>';
	} else {
?>
	<div class="head">
		<h3>Administrators<a href="administrators/add" class="add">Add admin</a></h3>
		<p>Manage administration accounts</p>
	</div>
<?php
		echo notices();
		foreach ($admins as $admin){
			echo'
			<div class="mini bloc">
			<h5>'.$admin->name.'
				<div class="tools">
					<a href="administrators/delete/'.$admin->id.'"><i class="icon-trash"></i></a>
					<a href="administrators/edit/'.$admin->id.'"><i class="icon-pencil"></i></a>
				</div>
			</h5>
			<p>'.$admin->email.'</p>
			</div>';
		}
	}
	echo $footer;
?>