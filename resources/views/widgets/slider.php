<div class="container">
	<div id="slider-widget" class="flexslider">
		<ul class="slides">
			<?php
			$slides = \App\Slide::orderby('id','desc')->get();
			foreach($slides as $slide){?>
				<li>
					<a href="<?=$slide->link?>">
						<img src="<?=url('/assets/slider/'.$slide->image)?>" />
					</a>
				</li>
			<?php }?>
		</ul>
	</div>
</div>
<link rel="stylesheet" href="themes/default/assets/flexslider.css" type="text/css">
<script src="themes/default/assets/jquery.flexslider.js"></script>
<script>
$(document).ready(function(){
	$('#slider-widget').flexslider({
		animation: "slide",
		controlNav: true,
		animationLoop: false,
		slideshow: false,
		touch: true,
		keyboard: true,
		smoothHeight: true, 
	});
});
</script>