<?php
	echo $header;
	if ($action == "details"){
		echo notices();
		echo '<br/><div class="col-md-12">
		<div class="list">
		<div class="title">
			<i class="icon-user"></i>Quote Requester details
		</div>
		<div class="item order"><h6><b>RFQ ID</b> : #'.$rfq->id.'</h6></div>
		<div class="item order"><h6><b>RFQ time</b> : '.date('l jS \of F Y H:i:s',$rfq->time).'</h6></div>
		';
		foreach($fields as $field){
			$code = $field->code;
			if ($code == 'country') {$rfq->$code = country($rfq->$code);}
			echo '<div class="item order"><h6><b>'.$field->name.'</b> : '.$rfq->$code.'</h6></div>';
		}
		echo '</div>
		<div class="list">
		<div class="title">
			<i class="icon-basket"></i>Products
		</div>';
			$products_data = $rfq->products;
			$products = json_decode($products_data, true);
			if(count($products)>0){
				$ids = "";
				foreach($products as $data){
					$ids = $ids . $data['id'] . ",";
					$q[$data['id']] = $data['quantity'];
					$options_list[$data['id']] = $data['options'];
				}
				$ids = rtrim($ids, ',');
				$rfq_products = \App\Product::whereRaw("id IN (".$ids.")")->orderby('id','desc')->get();
				foreach ($rfq_products as $product){
					$options = json_decode($options_list[$product->id],true);
					$option_array = array();
					foreach ($options as $option) {
						$option_array[] = '<i>'.$option['title'].'</i> : '.$option['value'];
					}
					echo '<div class="item"><h6>'.$product->title.' x '.$q[$product->id].'<b class="pull-right">'.currency($product->price).'</b><br/>'.implode('<br/>',$option_array).'</div>';
				}
				
				if (!empty($rfq->coupon)) {
					// Check if coupon is valid
					if (\App\Coupon::where("code",$rfq->coupon)->count() > 0){
						$coupon_data = \App\Coupon::where("code",$rfq->coupon)->first();
						echo '<div class="item order text-right"> Coupon : <b>'.$coupon_data->discount.$coupon_data->type.'</b></div>';
					}
				}
				if ($rfq->shipping != 0) {
					// Check if coupon is valid
					echo '<div class="item order text-right"> Shipping : <b>'.currency($rfq->shipping).'</b></div>';
				}
				echo '<div class="item order text-right">Total : <b>'.currency($rfq->summ).'</b></div>';
			}
		echo '</div>
		<div class="text-center"><a href="rfqs/delete/'.$rfq->id.'" class="btn btn-danger">Delete RFQ</a></div>
		</div>';
	} else {
		echo '<div class="head">
			<h3>RFQs</h3>
			<p>View and manage your the Request for Quotes</p>
		</div>
		<div>';
		echo notices();
		foreach ($rfqs as $rfq){
			echo '<div class="bloc status-'.$rfq->stat.'">
			<h5>'.$rfq->name.'<div class="tools">
			<a href="rfqs/details/'.$rfq->id.'"><i class="icon-eye"></i></a>
			<a href="rfqs/delete/'.$rfq->id.'"><i class="icon-trash"></i></a>
			</div></h5>
			<p>';
			foreach($fields as $field){
				$code = $field->code;
				if ($field->code == 'country') {$rfq->$code = country($rfq->$code);}
				echo $field->name.' : '.$rfq->$code.'<br/>';
			}
			echo '</p><div class="op">';
			$products = json_decode($rfq->products, true);
			if(count($products)>0){
				$ids = "";
				foreach($products as $data){
					$ids = $ids . $data['id'] . ",";
					$q[$data['id']] = $data['quantity'];

				}
				$ids = rtrim($ids, ',');
				$products = \App\Product::whereRaw("products WHERE id IN (".$ids.")")->orderby('id','desc');
				$total_price=0;
				foreach ($products as $product){
					echo '<div id='.$product->id.' ><p>'.$product->title.' x '.$q[$product->id].'</p><b>'.$product->price.'$</b><div style="clear:both"></div></div>';
				}
				
				echo '<b>Total : '.currency($rfq->summ).'</b>';
			}
			echo'<div style="clear:both;"></div></div></div>';
		}
		echo'</div>';
	}
?>
<style>
	<?php
		foreach ($status_array as $status_id => $status){
			echo '.status-'.$status_id.' {border-top: 4px solid '.$status['color'].';}';
		}
	?>
</style>
<?php
	echo $footer;
?>