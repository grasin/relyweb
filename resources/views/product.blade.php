<?php echo $header?>
<div class="container">
	<div class="content product-page">
		<div class="col-md-6">
			<div id="slider" class="flexslider">
				<ul class="slides">
					@foreach($images as $image)
						<li class="zoom">
							<img src="<?=url('/assets/products/'.$image)?>" />
						</li>
					@endforeach
				</ul>
			</div>
			<div id="carousel" class="flexslider">
				<ul class="slides">
					@foreach($images as $image)

						<li>
							<img src="<?=url('/assets/products/'.$image)?>" />
						</li>
					@endforeach

				</ul>
			</div>
		</div>
		<div class="col-md-6">
			<a href="{{url('/products/'.$cat->path)}}" class="smooth">{{translate($cat->name)}}</a>
			<h3>{{translate($product->title)}}</h3>
            <hr>
			{{string_cut(translate($product->short_text),600,' ...')}}
<!--			<h5 class="price">{{currency($product->price)}}</h5>-->
			<br>
			<br>
            <div class="order">
                <div class="quantity-select">
                    <div class="dec rease">-</div>
                    <input name="quantity" class="quantity" value="1" >
                    <div class="inc rease">+</div>
                </div>
					<button class="add-cart bg" data-id="{{$product->id}}">{{translate('Request Quote')}}</button>
			</div>
			<div class="share">
				<b><?=translate('Share')?> </b>
				<a href="https://www.facebook.com/sharer/sharer.php?u=<?=url()->current()?>"><i class="icon-social-facebook"></i></a> 
				<a href="https://twitter.com/intent/tweet/?url=<?=url()->current()?>"><i class="icon-social-twitter"></i></a>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
	<div class="content">
		<div class="tabs">
			<a href="#description" data-toggle="tab" class="active"><?=translate('Description')?></a>
			<a href="#reviews" data-toggle="tab"><?=translate('Reviews')?> (<?=$total_ratings?>)</a>
		</div>
		<div class="tab-content">
			<div class="tab-pane active" id="description"><?=nl2br(translate($product->text))?></div>
			<div class="tab-pane" id="reviews">
				<?php 
					foreach($reviews as $review){
						echo '<img class="review-image" src="https://www.gravatar.com/avatar/'.md5($review->email).'?s=45&d=mm">
						<div class="review-meta"><b>'.$review->name.'</b><br/>
						<span class="time">'.date('M d, Y',$review->time).'</span><br/></div>
						<div class="review">
						<div class="rating pull-right">';
						$rr = $review->rating; $i = 0; while($i<5){ $i++;?>
						<i class="star<?=($i<=$review->rating) ? '-selected' : '';?>"></i>
						<?php $rr--; }
						echo '</div>
						<div class="clearfix"></div>
						<p>'.nl2br($review->review).'</p></div>';
					}
					if(count($reviews) > 0){
						echo '<hr/>';
					}
				?>
				<form action="" method="post" id="review" class="form-horizontal single">
					<div id="response"></div>
					<h5><?=translate('Add a review')?> :</h5>
					<fieldset>
						<div class="row">
							<div class="form-group col-md-4">
								<label class="control-label"><?=translate('Name')?></label>
								<input name="name" value="" class="form-control" type="text">
							</div>
							<div class="form-group col-md-4">
								<label class="control-label"><?=translate('E-mail')?></label>
								<input name="email" value="" class="form-control" type="text">
							</div>
							<div class="form-group col-md-4">
								<label class="control-label"><?=translate('Rating')?></label>
								<div id="star-rating">
									<input type="radio" name="rating" class="rating" value="1" />
									<input type="radio" name="rating" class="rating" value="2" />
									<input type="radio" name="rating" class="rating" value="3" />
									<input type="radio" name="rating" class="rating" value="4" />
									<input type="radio" name="rating" class="rating" value="5" />
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label"><?=translate('Review')?></label>
							<textarea name="review" type="text" rows="5" class="form-control"></textarea>
						</div>
						<button data-product="<?=$product->id?>" name="submit" id="submit-review" class="btn btn-primary" ><?=translate('submit')?></button>
					</fieldset>
				</form>
			</div>
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
		$('#star-rating').rating();
		$('#carousel').flexslider({
			animation: "slide",
			controlNav: false,
			animationLoop: false,
			slideshow: false,
			itemWidth: 210,
			itemMargin: 5,
			minItems: 4,
			maxItems: 6,
			asNavFor: '#slider'
		});
		
		$('#slider').flexslider({
			animation: "slide",
			controlNav: false,
			animationLoop: false,
			slideshow: false,
			sync: "#carousel",
			touch: true,
			keyboard: true,
			smoothHeight: true, 
		});
		$('.zoom').zoom({magnify: 3});
		var maxQuantity = <?=$product->quantity?>;
		$("body").off('click',".rease").on('click',".rease", function() {
			
			var $button = $(this);
			var oldValue = $button.parent().find("input").val();
			if ($button.text() == "+") {
				if (oldValue < maxQuantity) {
					var newVal = parseFloat(oldValue) + 1;
				} else {
					newVal = maxQuantity;
				}
			} else {
				if (oldValue > 1) {
					var newVal = parseFloat(oldValue) - 1;
				} else {
					newVal = 1;
				}
			}
			
			$button.parent().find("input").val(newVal);
			
		});
		$("body").off('change').on('change',".quantity", function() {
			var $button = $(this);
			var oldValue = $button.val();
			if (oldValue > maxQuantity) {
				var newVal = maxQuantity;
			} else if (oldValue < 1) {
				var newVal = 1;
			}
			$button.parent().find("input").val(newVal);
		});
	});
</script>
<?php echo $footer?>