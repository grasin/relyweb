<?php echo $header?>
	<div class="bheader bg">
		<h2>Request for Quote</h2>
	</div>
	<div id="non-floating-cart">
			<div id="cart-header_page">
				Request for Quote
			</div>
			<div id="cart-content_page">
				<div class="loading"></div>
			</div>
	</div>
	<script>
		$("#cart-content_page").html('<div class="loading"></div>');
		$.getJSON('api/cart', function (data){
			$("#cart-header_page").html(data.header);
			if (data.count > 0){
				var cart = '';
				$.each(data.products, function(index,elem){
					cart += '<div class="cart-product"><img src="assets/products/'+elem.images+'"><div class="details"><h6>'+elem.title+'<i data-id="'+elem.id+'" class="remove-cart icon-trash"></i></h6></div><div class="clearfix"></div></div>';
				});
				cart += '<div class="btn-clear"></div><button class="cart-btn cart-checkout bg">'+request_quote+'</button>';
				$("#cart-content_page").html(cart);
			}else{
				$("#cart-content_page").html('<div class="empty-cart"><i class="icon-tag"></i><h5>'+no_products+'</h5></div>');
			}
		});
		$('#cart-content_page').slimScroll({
			height: 'auto',
			scrollTo : 0,
		});
	</script>
<?php echo $footer?>