<?php echo $header?>
<div class="container landing-cover">
	<div class="col-md-6 no-padding">
		<h1 class="landing-heading">Your next generation sourcing partner</h1>
		<p class="landing-details">All your electrical equipments and machinery needs fullfilled at one stop! Realiable partner for all of your engineering needs.</p>
		<a class="landing-cta smooth c" href="<?=explode(',',$style->button)[1] ?>" data-title="Products"><?=translate(explode(',',$style->button)[0]) ?></a>
	</div>
	<div class="landing-media col-md-6 no-padding">
        <div id="slider" class="flexslider">
				<ul class="slides">
						<li class="zoom">
							<img src="/assets/slider/Motor-cutout.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/cable%20ties.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/cables.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/circuit-breakers.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/cover.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/green_2.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/green_4.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/meters.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/panels.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/relay.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/sensors.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/singlepole_1.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/solar.png" />
						</li>
                        <li class="zoom">
							<img src="/assets/slider/transformer.png" />
						</li>
				</ul>
			</div>
	</div>
</div>




<link rel="stylesheet" href="<?=$tp;?>/assets/flexslider.css" type="text/css">
<script src="<?=$tp;?>/assets/jquery.flexslider.js"></script>
<script src="<?=$tp;?>/assets/jquery.zoom.js"></script>
<style>
	.zoomImg {
	background: white;
	}
</style>
<script>
	$(document).ready(function(){
		
		$('#slider').flexslider({
			animation: "slide",
            slideshowSpeed: 1000,
			controlNav: false,
			animationLoop: true,
			slideshow: true,
			touch: true,
			keyboard: true,
			smoothHeight: true, 
		});

	});
</script>

<?php
	foreach ($blocs as $bloc){
		if (mb_substr($bloc->content, 0, 7) == 'widget:') {
			echo $__env->make('widgets/'.mb_substr($bloc->content, 7, 255))->render();
		} else {
			echo $bloc->content;
		}
	}
echo $footer
?>
