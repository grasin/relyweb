<?php
	echo $header;
	if($action == "add")
	{
		echo notices().'<form action="" method="post" enctype="multipart/form-data" class="form-horizontal single">
				'.csrf_field().'
				<h5><a href="slider"><i class="icon-arrow-left"></i></a>Add new slide</h5>
					<fieldset>
						  <div class="form-group">
							<label class="control-label">Slide name</label>
							<input name="title" type="text"  class="form-control" required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Slide link</label>
							<input name="link" type="text"  class="form-control" required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Slide image</label>
							<input type="file" class="form-control" name="image" required/>
						  </div>
						  <input name="add" type="submit" value="Add slide" class="btn btn-primary" />
					</fieldset>
				</form>';
	} elseif($action == "edit"){
		echo notices().'<form action="" method="post" enctype="multipart/form-data" class="form-horizontal single">
			'.csrf_field().'
				<h5><a href="slider"><i class="icon-arrow-left"></i></a>Update slide</h5>
					<fieldset>
						  <div class="form-group">
							<label class="control-label">Slide name</label>
							<input name="title" type="text" value="'.$slide->title.'" class="form-control"  required/>
						  </div>
						  <div class="form-group">
							<label class="control-label">Slide link</label>
							<input name="link" type="text" value="'.$slide->link.'" class="form-control"  required/>
						  </div>
						  <p>Uploading new file will overwrite current file .</p>
						  <div class="form-group">
							<label class="control-label">Image</label>
							<input type="file" class="form-control" name="image" required/>
						  </div>
						  <input name="edit" type="submit" value="Update slide" class="btn btn-primary" />
					</fieldset>
				</form>';
	} else { ?>
	<div class="head">
	<h3>Slider<a href="slider/add" class="add">Add slide</a></h3>
	<p>Manage your website front page slides </p>
	</div>
<?php
	echo notices();
	foreach ($slides as $slide){
	echo '<div class="bloc">
				<h5>
					'.$slide->title.'
					<div class="tools">
						<a href="slider/delete/'.$slide->id.'"><i class="icon-trash"></i></a>
						<a href="slider/edit/'.$slide->id.'"><i class="icon-pencil"></i></a>
					</div>
				</h5>
				<img src="../assets/slider/'.$slide->image.'" width="100%">
			</div>';}
	}
?>
<?php
	echo $footer;
?>