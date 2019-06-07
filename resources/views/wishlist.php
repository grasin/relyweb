<?php echo $header?>
	<div class="bheader bg">
		<h2><?=translate('Wishlist')?></h2>
	</div>
	<div class="container">
		<div class="product-container row" style="padding: 100px 0px;">
			<?php	
				if (count($products) > 0) {
					foreach($products as $product){
						echo '<div class="col-md-3">
							<div class="product" id="'.$product->id.'">
								<div class="pi">
									<img src="'.url('/assets/products/'.image_order($product->images)).'"/>
								</div>
								<h5>'.translate($product->title).'</h5>
								<b>'.currency($product->price).'</b>
							</div>
							<div class="bg view">
								<h5>'.translate($product->title).'</h5>
								<p>'.mb_substr(translate($product->text),0,200).'</p>
								<a href="product/'.path($product->title,$product->id).'" data-title="'.translate($product->title).'" class="smooth">
									<i class="icon-eye"></i>
									Details
								</a>
							</div>
						</div>';
					}
				} else {
					echo '<div class="text-center"><h1><i class="icon-ghost"></i></h1><p>'.translate('You don\'t have any products in your wishist').'</p></div>';
				}
			?>
		</div>
	</div>
<?php echo $footer?>