<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Api extends Controller
{
	public $cfg;
    public $cart;
    public $cart_options;

    public function __construct()
    {
        $this->cfg = \App\Config::first();
        $this->cart = request()->cookie('cart') != null ? json_decode(stripslashes(request()->cookie('cart')),true) : array();
        $this->cart_options = request()->cookie('cart_options') != null ? json_decode(request()->cookie('cart_options'),true) : array();
		$this->wishlist = request()->cookie('wishlist') != null ? json_decode(stripslashes(request()->cookie('wishlist')),true) : array();
    }
	public function register()
	{
		if ($this->cfg->registration == 0) {
			return response()->json(["error"=>"Registration is disabled"]);
		}
		if (!empty(request()->name) && !empty(request()->email) && !empty(request()->password)){
			$data['name'] = htmlspecialchars(request()->name);
			$data['email'] = htmlspecialchars(request()->email);
			$data['password'] = md5(request()->password);
			$data['sid'] = md5(microtime().uniqid());
			if(\App\Customer::where(['email' => $data['email']])->count() > 0) {
				return response()->json(["error"=>"This email is already registerd !"]);
			} else {
				\App\Customer::insert($data);
				return response()->json(["success"=>"Your account has been registred successfully!"]);
			}
		} else {
			return response()->json(["error"=>"All fields are required !"]);
		}		
	}
	public function login()
	{
		if ($this->cfg->registration == 0) {
			return response()->json(["error"=>"Registration is disabled"]);
		}
		// Check email and password and redirect to the account
		$data['email'] = htmlspecialchars(request()->email);
		$data['password'] = md5(request()->password);
		if(empty($data['email']) or empty($data['password'])){
			return response()->json(["error"=>"All fields are required !"]);
		} else {
			if(\App\Customer::where($data)->count() > 0) {
				// Generate a new secure ID for this session and redirect to dashboard
				$secure_id = md5(microtime().uniqid());
				\App\Customer::where($data)->update(['sid' => $secure_id]);
				return response()->json(["success"=>"You have been logged in successfully!","sid"=>$secure_id]);
			} else {
				return response()->json(["error"=>"Wrong email or password"]);
			}
		}
	}
	public function account()
	{
		$customer = \App\Customer::where('sid',request()->sid)->first();
		if (!isset($customer->id)){
			return response()->json(["error"=>"Wrong session id"]);
		}
		$response['id'] = $customer->id;
		$response['name'] = $customer->name;
		$response['email'] = $customer->email;
		$orders = \App\Order::where('customer',$customer->id)->orderby('id','desc')->get();
		$fields = \App\Field::orderby('id','asc')->get();
		$orders_array = array();
		foreach ($orders as $order){
			$orders_array[$order->id]['id'] = $order->id;
			foreach($fields as $field){
				$code = $field->code;
				if ($code == 'country') {$order->$code = country($order->$code);}
				$orders_array[$order->id][$code] = $order->$code;
			}
			$products = json_decode($order->products, true);
			if(count($products)>0){
				$ids = "";
				foreach($products as $data){
					$ids = $ids . $data['id'] . ",";
					$q[$data['id']] = $data['quantity'];
					$options_list[$data['id']] = $data['options'];
				}
				$ids = rtrim($ids, ',');
				$order_products = \App\Product::whereRaw("id IN (".$ids.")")->orderby('id','desc')->get();
				foreach ($order_products as $product){
					$options = json_decode($options_list[$product->id],true);
					$option_array = array();
					foreach ($options as $option) {
						$option_array[] = ["title" => $option['title'],"value" => $option['value']];
					}
					$products_array[] = ["title" => $product->title,"quantity" => $q[$product->id],"price" => currency($product->price),"options" => $option_array];
				}
				$orders_array[$order->id]['products'] = $products_array;
				if (!empty($order->coupon)) {
					if (\App\Coupon::where("code",$order->coupon)->count() > 0){
						$coupon_data = \App\Coupon::where("code",$order->coupon)->first();
						$orders_array[$order->id]['discount'] = ["discount" => $coupon_data->discount,"type" => $coupon_data->type];
					}
				}
				if ($order->shipping != 0) {
					// Check if coupon is valid
					$orders_array[$order->id]['shipping'] = currency($order->shipping);
				}
				$orders_array[$order->id]['total'] = currency($order->summ);
				$orders_array[$order->id]['payment'] = json_decode($order->payment);
			}
		}
		$response['orders'] = $orders_array;
		return response()->json($response);
	}
    public function products()
	{
		// Apply the product filters
		$where = array();
		if (!empty(request()->min) && !empty(request()->max))
		{
			$where['price'] = "price BETWEEN '".escape(request()->min)."' AND '".escape(request()->max)."'";
		}
		if (!empty(request()->search))
		{
			$where['search'] = "(title LIKE '%".escape(request()->search)."%' OR text LIKE '%".escape(request()->search)."%')";
		}
		if (!empty(request()->cat))
		{
			$category = \App\Category::where('id',request()->cat)->first();
			$categories[] = $category->id;
			if ($category->parent == 0){
				$childs = \App\Category::where('parent',$category->id)->orderby('id','desc')->get();
				foreach ($childs as $child){
					$categories[] = $child->id;
				}
			}
			$where['cat'] = "category IN (".implode(',',$categories).")";
		}
		$where = ($where) ?  implode(' AND ', $where) : '1';
		$products = \App\Product::whereRaw($where)->orderby('id','desc')->get();
		$response = array();
		$response['products'] = array();
		// fetch products and return them in json format
		foreach ($products as $row){
			$data['id'] = $row->id;
			$data['images'] = url('/assets/products/'.image_order($row->images));
			$data['title'] = $row->title;
			$data['text'] = mb_substr(translate($row->text),0,200);
			$data['path'] = 'product/'.path($row->title,$row->id);
			$data['price'] = currency($row->price);
			array_push($response["products"], $data);
		}
		return response()->json($response);
	}
    public function posts()
	{
		// Apply the posts filter
		$where = array();
		if (!empty(request()->search))
		{
			$where['search'] = "(title LIKE '%".escape(request()->search)."%' OR content LIKE '%".escape(request()->search)."%')";
		}
		$where = ($where) ? implode(' AND ', $where) : '1';
		$posts = \App\Post::whereRaw($where)->orderby('id','desc')->get();
		$response = array();
		$response['posts'] = array();
		// fetch posts and return them in json format
		foreach ($posts as $row){
			$data['id'] = $row->id;
			$data['image'] = url('/assets/products/'.$row->images);
			$data['title'] = $row->title;
			$data['content'] = mb_substr(translate($row->content),0,200);
			$data['path'] = 'blog/'.path($row->title,$row->id);
			$data['time'] = timegap($row->time).translate(' ago');
			$data['timestamp'] = $row->time;
			array_push($response["posts"], $data);
		}
		return response()->json($response);
	}
	public function wish()
	{
		if (!isset(request()->id)) { return 'No product has been selected'; }
		$id = request()->id;
		$wishlist_items = array();
		if(count($this->wishlist)>0){
			foreach($this->wishlist as $value){
				$wishlist_items[$value]=$value;
			}
		}
		if(!array_key_exists($id, $this->wishlist)){
			$wishlist_items[$id] = $id;
			echo 'Added successfully';
		} else {
			unset($wishlist_items[$id]);
			echo 'Removed successfully';
		}
		$json = json_encode($wishlist_items, true);
		setcookie('wishlist', $json,time()+31536000,'/');
	}
	public function add()
	{
		if (!isset(request()->id)) { return 'No product has been selected'; }
		$id = request()->id;
		$q = isset(request()->q) ? (int)request()->q : "1";
		$cart_items = array();
		$cart_items[$id] = $q;
		$options = json_decode(\App\Product::select('options')->where('id',$id)->first()->options,true);
		$option_list = array();
            if(!empty($options)){
                foreach ($options as $option) {
                    $name = $option['name'];
                    $title = $option['title'];
                    $option_list[$name] = array('title'=>$title,'value'=>(isset(request()->$name) ? ($option['type'] == 'multi_select' ? implode(' , ',request()->$name) : request()->$name) : ''));
                }
            }
			$cart_options[$id] = json_encode($option_list);
		if (\App\Product::select('quantity')->where('id',$id)->first()->quantity < $q){
			// Throw stock unavailable error
			return 'unavailable';
		}
		// Check if the item is in the array, if it is, update quantity
		if(array_key_exists($id, $this->cart)){
			foreach($this->cart as $key => $value){
				$cart_items[$key] = $value;
			}
			unset($cart_items[$id]);
			if ($q > 0){
				$cart_items[$id] = $q;
			}
			$json = json_encode($cart_items, true);
			// Update cart cookie
			setcookie('cart', $json,time()+31536000,'/');
			foreach($this->cart_options as $key => $value){
				$cart_options[$key] = $value;
			}
			unset($cart_options[$id]);
			$cart_options[$id] = json_encode($option_list);
			$json = json_encode($cart_options, true);
			// Update cart options cookie
			setcookie('cart_options', $json,time()+31536000,'/');
			return 'updated';
		} else {
			if(count($this->cart)>0){
				foreach($this->cart as $key=>$value){
					// add old item to array, it will prevent duplicate keys
					$cart_items[$key]=$value;
				}
			}
			$json = json_encode($cart_items, true);
			// Update cart cookies
			setcookie('cart', $json,time()+31536000,'/');
			if(count($this->cart_options)>0){
				foreach($this->cart_options as $key=>$value){
					// add old item to array, it will prevent duplicate keys
					$cart_options[$key]=$value;
				}
			}
			$json = json_encode($cart_options, true);
			// Update cart cookies
			setcookie('cart_options', $json,time()+31536000,'/');
			return 'success';
		}
	}
	public function cart()
	{
		$cart_items = $this->cart;
		// Append items count to the response
		$response["count"] = count($cart_items);
		// Cart header
		$response['header'] = "Request for Quote";
		if($response["count"] > 0){
			// get the product ids
			$ids = "";
			foreach($cart_items as $id=>$name){
				$ids = $ids . $id . ",";
			}
			// remove the last comma
			$ids = rtrim($ids, ',');
			// Get products from database
			$cart_products = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
			$total_price = 0;
			$total_p = 0;
			$response["products"] = array();
			foreach ($cart_products as $row){
				$q = $this->cart[$row->id];
				$options = json_decode($this->cart_options[$row->id],true);
				$option_array = array();
				foreach ($options as $option) {
                    $option_array[] = '<i>'.$option['title'].'</i> : '.$option['value'];
                }
				$data['id'] = $row->id;
				$data['images'] = image_order($row->images);
				$data['title'] = $row->title;
				$data['price'] = currency($row->price);
				$data['quantity'] = $q;
				$data['options'] = implode('<br/>',$option_array);
				$data['total'] = currency($row->price * $q);
				// Add product to array :
				array_push($response["products"], $data);
				$total_p+= $q;
			}
		}
		if (request()->cookie('coupon') != null){
			// Check if coupon applied
			if (DB::select("SELECT COUNT(*) as count FROM coupons WHERE code = '".request()->cookie('coupon')."'")[0]->count > 0){
				$coupon = DB::select("SELECT * FROM coupons WHERE code = '".request()->cookie('coupon')."'")[0];
				$response["coupon"] = '<button data-toggle="collapse" class="btn-coupon" data-target="#coupon">'.translate('Coupon').' : <b>'.request()->cookie('coupon').' ('.$coupon->discount.$coupon->type.')</b></button>
				<div id="coupon" class="collapse">
				<input placeholder="'.translate('Coupon code').'" value="'.request()->cookie('coupon').'" class="coupon" id="code"/>
				<button id="apply" class="btn-apply">'.translate('Apply').'</button>
				</div>';
			}
		} else {
			$response["coupon"] = '
			<button data-toggle="collapse" class="btn-coupon" data-target="#coupon">'.translate('Have a coupon code ?').'</button>
			<div id="coupon" class="collapse">
			<input placeholder="'.translate('Coupon code').'" class="coupon" id="code"/>
			<button id="apply" class="btn-apply">'.translate('Apply').'</button>
			</div>';
		}
		return response()->json($response);
	}
	public function remove()
	{
		// Remove product from cart
		$cart_items = array();
		foreach($this->cart as $key=>$value){
			$cart_items[$key] = $value;
		}
		unset($cart_items[request()->id]);
		$json = json_encode($cart_items, true);
		setcookie('cart', $json,time()+31536000,'/');
		$cart_options = array();
		foreach($this->cart_options as $key=>$value){
			$cart_options[$key] = $value;
		}
		unset($cart_options[request()->id]);
		$json = json_encode($cart_options, true);
		setcookie('cart_options', $json,time()+31536000,'/');
		return 'removed';
	}
	public function checkout()
	{
		$response = array();
		$fields = \App\Field::orderby('id','asc')->get();
		$response[] = '<form id="customer"><div id="errors"></div>';
		foreach ($fields as $field){
			if ($field->code == 'country'){
				// Return country selector if code of field is country
				$options = '';
				$countries = \App\Country::orderby('nicename','asc')->get();
				foreach ($countries as $country){
					$options .= '<option value="'.$country->iso.'" data-phone="'.$country->phonecode.'">'.$country->nicename.'</option>';
				}
				$response[] = '<div class="form-group">
					<label class="control-label">'.translate($field->name).'</label>
					<select id="country" name="'.$field->code.'" class="form-control" type="text">'.$options.'</select>
				</div>';
			} else {
				$response[] = '<div class="form-group">
					<label class="control-label">'.translate($field->name).'</label>
					<input name="'.$field->code.'" value="'.customer($field->code).'" class="form-control" type="'.($field->code == 'mobile' ? 'number' : 'text').'">
				</div>';
			}
		}
		$response[] = '</form>';
		return response()->json($response);
	}
    
    
    
	public function get_quote(){
        $response = array();
        $email_fields = '';
		$email_products = '';
        $fields = \App\Field::orderby('id','asc')->get();
		foreach ($fields as $field){
			// Retrieve POSTed data , or throw error if they aren't filled
			if(!empty(request()->input($field->code))){
				$data[$field->code] = request()->input($field->code);
				$email_fields .= $data[$field->code].'<br />';
			} else {
				$response['error'] = 'true';
				$response['message'] .= translate($field->name).' '.translate('field is required').'<br/>';
			}
		}
		if (isset($response['error'])){
			return response()->json($response);
		}
        
        
		$cart_items = $this->cart;
		if(count($cart_items) > 0){
			$ids = "";
			foreach($cart_items as $id=>$name){
				$ids = $ids . $id . ",";
			}
			$ids = rtrim($ids, ',');
			$cart_products = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
			$total = 0;
			$products = array();
			foreach ($cart_products as $row){
				$q = $this->cart[$row->id];
				$product['id'] = $row->id;
				$product['quantity'] = $q;
				$product['options'] = $this->cart_options[$row->id];
				array_push($products,$product);
				$total += $row->price * $q;
				$email_products .= '<div>'.$row->title.' x '.$q.'<b style="float:right">'.currency($row->price * $q).'</b></div><hr>';
			}
		} else {
			return 'Your cart is empty';
		}
        
        
        
        $data['products'] = json_encode($products,true);
		$data['customer'] = (session('customer') == '' ? '0' : customer('id'));
		$data['summ'] = $total;
		$data['time'] = time();
		$data['date'] = date('Y-m-d');
		$data['stat'] = 1;
		$data['payment'] = '{"payment_status":"unpaid"}';
		$columns = implode(", ",array_keys($data));
		$values  = implode("', '", $data);
		// Update orders per country
		\App\Country::where('iso',$data['country'])->increment('orders');
		// Save order in database
		$rfq = DB::table('rfqs')->insertGetId($data);
		// Send an email to customer
		mailing('rfq',array('buyer_name'=>$data['name'],'buyer_email'=>$data['email'],'buyer_fields'=>$email_fields,'name'=>$this->cfg->name,'address'=>$this->cfg->address,'phone'=>$this->cfg->phone,'products'=>$email_products,'total'=>currency($total)),'RFQ Confirmation #'.$rfq,$data['email']);
		// Get payment methods
        return response()->json($response);
        
    }
    
    
	public function payment()
	{
		$response = array();
		$error = '';
		$success = '';
		// Payment header
		$response['header'] = translate('Payment').'<button class="pull-right" onclick="$(\'#cart\').toggleClass(\'cart-open\');$(\'#cart\').toggle(\'300\');"><i class="icon-close"></i></button>';
		$response['message'] = '';
		$email_fields = '';
		$email_products = '';
		$fields = \App\Field::orderby('id','asc')->get();
		foreach ($fields as $field){
			// Retrieve POSTed data , or throw error if they aren't filled
			if(!empty(request()->input($field->code))){
				$data[$field->code] = request()->input($field->code);
				$email_fields .= $data[$field->code].'<br />';
			} else {
				$response['error'] = 'true';
				$response['message'] .= translate($field->name).' '.translate('field is required').'<br/>';
			}
		}
		if (isset($response['error'])){
			return response()->json($response);
		}
		$cart_items = $this->cart;
		if(count($cart_items) > 0){
			$ids = "";
			foreach($cart_items as $id=>$name){
				$ids = $ids . $id . ",";
			}
			$ids = rtrim($ids, ',');
			$cart_products = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
			$total = 0;
			$products = array();
			foreach ($cart_products as $row){
				$q = $this->cart[$row->id];
				$product['id'] = $row->id;
				$product['quantity'] = $q;
				$product['options'] = $this->cart_options[$row->id];
				array_push($products,$product);
				$total += $row->price * $q;
				$email_products .= '<div>'.$row->title.' x '.$q.'<b style="float:right">'.currency($row->price * $q).'</b></div><hr>';
				// Update product quantity
				\App\Product::where('id',$product['id'])->decrement('quantity',$q);
			}
		} else {
			return 'Your cart is empty';
		}
		$sub_total = $total;
		$coupon_response = '';
		$data['coupon'] = '';
		if (request()->cookie('coupon') != null) {
			// Check if coupon is valid
			if (\App\Coupon::where('code',request()->cookie('coupon'))->count() > 0){
				$coupon_data = \App\Coupon::where('code',request()->cookie('coupon'))->first();
				if ($coupon_data->type == '%'){
					$total = $total - ($total * $coupon_data->discount / 100);
				} else {
					$total = $total - $coupon_data->discount;
				}
				$email_products .= '<div>Coupon discount<b style="float:right">'.$coupon_data->discount.$coupon_data->type.'</b></div><hr>';
				$coupon_response = '<div>Coupon discount <b>'.$coupon_data->discount.$coupon_data->type.'</b></div>';
				$data['coupon'] = request()->cookie('coupon');
			}
		}
		$shipping_response = '';
		$data['shipping'] = '0';
		if (!empty($data['country'])) {
			// Add shipping cost
			if (\App\Shipping::where('country',$data['country'])->count() > 0){
				$shipping_data = \App\Shipping::where('country',$data['country'])->first();
				$total = $total + $shipping_data->cost;
				$email_products .= '<div>Shipping<b style="float:right">'.currency($shipping_data->cost).'</b></div><hr>';
				$shipping_response = '<div>Shipping <b>'.currency($shipping_data->cost).'</b></div>';
				$data['shipping'] = $shipping_data->cost;
			}
		}
		$data['products'] = json_encode($products,true);
		$data['customer'] = (session('customer') == '' ? '0' : customer('id'));
		$data['summ'] = $total;
		$data['time'] = time();
		$data['date'] = date('Y-m-d');
		$data['stat'] = 1;
		$data['payment'] = '{"payment_status":"unpaid"}';
		$columns = implode(", ",array_keys($data));
		$values  = implode("', '", $data);
		// Update orders per country
		\App\Country::where('iso',$data['country'])->increment('orders');
		// Save order in database
		$order = DB::table('orders')->insertGetId($data);
		// Send an email to customer
		mailing('order',array('buyer_name'=>$data['name'],'buyer_email'=>$data['email'],'buyer_fields'=>$email_fields,'name'=>$this->cfg->name,'address'=>$this->cfg->address,'phone'=>$this->cfg->phone,'products'=>$email_products,'total'=>currency($total)),'Order Confirmation #'.$order,$data['email']);
		// Get payment methods
		$methods_query = \App\Payment::where('active',1);
		if ($total == 0){
			$methods_query = $methods_query->where('code','cash');
		}
		$methods = $methods_query->orderby('id','asc')->get();
		$response[] = '<div class="payment_total"><div>Sub total <b>'.currency($sub_total).'</b></div>'.$coupon_response.$shipping_response.'<div>Total <b>'.currency($total).'</b></div></div><div class="payments">';
		foreach($methods as $method){
			// Get method options and include it 
			$options = json_decode(stripslashes($method->options), true);
			include app_path()."/Payments/".$method->code."/checkout.php";
		}
		$response[] = '</div>';
		return response()->json($response);
	}
	public function pay()
	{
		// Process order payment
		$order = (int)request()->order;
		if (!isset(request()->method) || !isset(request()->order)){
			return 'Invalid parameters';
		}
		if (!in_array(request()->method,array('stripe','cash','bank'))){
			return 'Invalid method';
		}
		if (\App\Order::where('id',$order)->count() == 0){
			return 'Invalid order';
		}
		$error = '';
		$success = '';
		$response = array();
		$unpaid = false;
		$will_pay = false;
		$response['message'] = '';
		$method = request()->method;
		$cart_items = json_decode(\App\Order::select('products')->where('id',$order)->first()->products,true);
		if(count($cart_items) > 0){
			$ids = "";
			foreach($cart_items as $cart_item){
				$ids = $ids . $cart_item['id'] . ",";
				$quantity[$cart_item['id']] = $cart_item['quantity'];
			}
			$ids = rtrim($ids, ',');
			$cart_products = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
			$total=0;
			$products = array();
			foreach ($cart_products as $row){
				$total += $row->price * $quantity[$row->id];
			}
		}
		$sub_total = $total;
		if (request()->cookie('coupon') != null) {
			// Check if coupon is valid
			if (\App\Coupon::where('code',request()->cookie('coupon'))->count() > 0){
				$coupon_data = \App\Coupon::where('code',request()->cookie('coupon'))->first();
				if ($coupon_data->type == '%'){
					$total = $total - ($total * $coupon_data->discount / 100);
				} else {
					$total = $total - $coupon_data->discount;
				}
			}
		}
		$order_shipping = \App\Order::select('shipping')->where('id',$order)->first()->shipping;
		$total = $total + $order_shipping;
		$payment_method = \App\Payment::where('code',$method)->first();
		if ($payment_method->active == 1){
			include app_path()."/Payments/".$payment_method->code."/payments.php";
		} else {
			return 'Method inactive';
		}
		if ($unpaid == true) {
			// If the order isn't paid show payment methods again
			$methods = \App\Payment::where('active',1)->orderby('id','asc')->get();
			$response['header'] = translate('Payment').'<button class="pull-right" onclick="$(\'#cart\').toggleClass(\'cart-open\');$(\'#cart\').toggle(\'300\');"><i class="icon-close"></i></button>';
			$response[] = '<div class="payments">';
			foreach($methods as $method){
				// Get method options and include it 
				$options = json_decode(stripslashes($method->options), true);
				include app_path()."/Payments/".$method->code."/checkout.php";
			}
		} else {
			// If the order has been paid , Show success message
			setcookie('cart', '',time()+31536000,'/');
			setcookie('cart_options', '',time()+31536000,'/');
			if ($will_pay == false) {
				// Send download link for digital products if paid successfully
				$this->send_downloads($order);
			}
			$response['header'] = translate('Success').'<button class="pull-right" onclick="$(\'#cart\').toggleClass(\'cart-open\');$(\'#cart\').toggle(\'300\');"><i class="icon-close"></i></button>';
			$response[] = '<div class="payment-success"><h3>'.translate('Thank you').'</h3>'.translate('Your order has been placed successfully').'</div>';
		}
		$response[] = '</div>';
		return response()->json($response);
	}
	public function paypal(){
		// Redirect to paypal to complete payment
		$method = \App\Payment::select('options')->where(['active' => 1,'code' => 'paypal'])->first()->options;
		$paypal_email = json_decode(stripslashes($method), true)['email'];
		if(isset(request()->order) || isset(request()->custom)){
			$order_id = isset(request()->order) ? request()->order : request()->custom;
			$order_json = \App\Order::where('id',$order_id)->first();
			$coupon = $order_json->coupon;
			$shipping = $order_json->shipping;
			$order = json_decode($order_json->products,true);
			$ids = "";
			foreach($order as $o){
				$ids = $ids . $o['id'] . ",";
				$q[$o['id']] = $o['quantity'];
			}
			$ids = rtrim($ids, ',');
			// Get order products
			$product = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
			$items = '';
			$i = 1;
			foreach ($product as $row){
				$items .= "item_name_".$i."=".urlencode(translate($row->title))."&";
				$items .= "amount_".$i."=".urlencode($row->price)."&";
				$items .= "quantity_".$i."=".urlencode($q[$row->id])."&";
				$i++;
			}
			$couponquery = '';
			if (!empty($coupon)) {
				// Check if coupon is valid
				if (\App\Coupon::where('code',$coupon)->count() > 0){
					$coupon_data = \App\Coupon::where('code',$coupon)->first();
					if ($coupon_data->type == '%'){
						$couponquery = "discount_rate_cart=".urlencode($coupon_data->discount)."&";
					} else {
						$couponquery = "discount_amount_cart=".urlencode($coupon_data['discount'])."&";
					}
				}
			}
			$shippingquery = "handling_cart=".urlencode($shipping)."&";
			// PayPal settings
			$return_url = url('/success');
			$cancel_url = url('/failed');
			$notify_url = url('/api/paypal');
			// Check if paypal request or response
			if (!isset(request()->txn_id) && !isset(request()->txn_type)){
				$querystring = '';
				
				// Firstly Append paypal account to querystring
				$querystring .= "?cmd=_cart&";
				$querystring .= "upload=1&";
				$querystring .= "business=".urlencode($paypal_email)."&";
				// $querystring .= "shopping_url =".urlencode($url)."&";
				
				//The item name and amount can be brought in dynamically by querying the $_POST['item_number'] variable.
				$querystring .= $items;
				$querystring .= $couponquery;
				$querystring .= $shippingquery;
				$querystring .= "currency_code=USD&";
				// Append paypal return addresses
				$querystring .= "return=".urlencode(stripslashes($return_url))."&";
				$querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
				$querystring .= "notify_url=".urlencode($notify_url);
				$querystring .= "&custom=".request()->order;			
				// Redirect to paypal IPN
				header('location:https://www.paypal.com/cgi-bin/webscr'.$querystring);
				exit();
			} else {
				// Response from Paypal
				// read the post from PayPal system and add 'cmd'
				$req = 'cmd=_notify-validate';
				foreach ($_POST as $key => $value) {
					$value = urlencode(stripslashes($value));
					$value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i','${1}%0D%0A${3}',$value);// IPN fix
					$req .= "&$key=$value";
				}
				
				// assign posted variables to local variables
				$data['method'] 	        = 'paypal';
				$data['payment_status'] 	= request()->payment_status;
				$data['payment_amount'] 	= request()->mc_gross;
				$data['payment_currency']	= request()->mc_currency;
				$data['txn_id']			    = request()->txn_id;
				$data['receiver_email'] 	= request()->receiver_email;
				$data['payer_email'] 		= request()->payer_email;
				$data['order'] 		    	= request()->custom;
				echo $payment = json_encode($data, true);
				
				// post back to PayPal system to validate
				$header = "POST /cgi-bin/webscr HTTP/1.1\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Host: www.paypal.com\r\n";  // www.paypal.com for a live site
				$header .= "Content-Length: " . strlen($req) . "\r\n";
				$header .= "Connection: close\r\n\r\n";
				
				$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
				
				if (!$fp) {
					// HTTP ERROR
					logger('HTTP ERROR');
				} else {
					fputs($fp, $header . $req);
					while (!feof($fp)) {
						$res = fgets($fp);
						// Payment verification
						if (strcmp($res, "VERIFIED") >= 0) {
							// another validation layer 
							if ($data['payment_status'] == 'Completed' && $data['receiver_email'] == $paypal_email) {
								// The payment is successful
								\App\Order::where('id',$data['order'])->update(['payment' => $payment]);
								// Send download link if digital product
								$this->send_downloads($data['order']);
								// for debugging
								logger('Successful payment');
							} else {
								// Payment unsuccessful - for debugging
								logger("The payment isn't completed yet !".$data['payment_status'].$data['receiver_email']);
							}
						} else if (strcmp ($res, "INVALID") == 0) {
							// Payment invalid - for debugging
							logger("The payment is invalid !");
						}
					}
					
					fclose ($fp);
				}
			}
		} else {
			header('location:'.url(''));
		}
	}
	public function send_downloads($order){
		$order_info = \App\Order::where('id',$order)->first();
		$order_products = json_decode($order_info->products,true);
		$ids = "";
		foreach($order_products as $product){
			$ids = $ids . $product['id'] . ",";
		}
		$ids = rtrim($ids, ',');
		// Get order products that can be downloaded
		$products = \App\Product::whereRaw("id IN ({$ids}) AND download != ''")->orderby('id','desc')->get();
		$email_downloads = '';
		foreach ($products as $row) {
			if ($row->download != ''){
				$email_downloads .= '<div>'.$row->title.'<b style="float:right"><a href="'.url('assets/downloads/'.$row->download).'">Download</a></b></div><hr>';
			}
		}
		if ($email_downloads != '') {
			mailing('download',array('downloads'=>$email_downloads),'Order Downloads #'.$order,$order_info->email);
		}
	}
	public function review(){
		if (empty(request()->email) || empty(request()->name) || empty(request()->product) || empty(request()->review) ||empty(request()->rating)){
			return 'All fields are required';
		} else {
			// escape review details and insert them into the database
			$data['email'] = htmlspecialchars(request()->email);
			$data['name'] = htmlspecialchars(request()->name);
			$data['rating'] = (int)request()->rating;
			$data['review'] = htmlspecialchars(request()->review);
			$data['product'] = (int)request()->product;
			$data['time'] = time();
			$data['active'] = 0;
			\App\Review::insert($data);
			return 'success';
		}
	}
	public function coupon(){
		// Check if coupon is valid and save it in cookies
		$code = htmlspecialchars(request()->code);
		if (\App\Coupon::where('code',$code)->count() > 0){
			setcookie('coupon', $code);
			return 'success';
		} else {
			return 'invalid';
		}
	}
	public function subscribe(){
		// Email subscribe
		$email = htmlspecialchars(request()->email);
		if (\App\Subscriber::where('email',$email)->count() > 0){
			return 'Already subscribed';
		} else {
			\App\Subscriber::insert(['email' => $email]);
			return 'successfully subscribed';
		}
	}
	public function orders(){
		$orders = \App\Order::orderby('id','desc')->get();
		$fields = \App\Field::orderby('id','asc')->get();
		$response = array();
		$response['orders'] = array();
		// fetch reviews and return them in json format
		foreach ($orders as $row){
			$data['id'] = $row->id;
			// return data per field
			foreach($fields as $field){
				$code = $field->code;
				if ($code == 'country') {
					$row->$code = country($row->$code);
				}
				$data[$code] = $row->$code;
			}
			$data['products'] = array();
			$products = json_decode($row->products, true);
			if(count($products)>0){
				// search for products and return product data and selected quantity
				$ids = "";
				foreach($products as $item){
					$ids = $ids . $item['id'] . ",";
					$q[$item['id']] = $item['quantity'];
				}
				$ids = rtrim($ids, ',');
				$products = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
				$total_price=0;
				foreach ($products as $item){
					$product['id'] = $item->id;
					$product['title'] = $item->title;
					$product['price'] = $item->price;
					$product['quantity'] = $q[$item->id];
					$product['total'] = $item->price*$q[$item->id];
					array_push($data['products'], $product);
				}
			}
			$data['coupon'] = $row->coupon;
			$data['total'] = $row->summ;
			array_push($response["orders"], $data);
		}
		return response()->json($response);
	}
	public function reviews(){
		$reviews = \App\Review::orderby('id','desc')->get();
		$response = array();
		$response['reviews'] = array();
		// fetch reviews and return them in json format
		foreach ($reviews as $row){
			$data['id'] = $row->id;
			$data['name'] = $row->name;
			$data['name'] = $row->name;
			$data['email'] = $row->email;
			$data['rating'] = $row->rating;
			$data['review'] = $row->review;
			$data['time'] = timegap($row->time);
			$data['timestamp'] = $row->time;
			array_push($response["reviews"], $data);
		}
		return response()->json($response);
	}
}
