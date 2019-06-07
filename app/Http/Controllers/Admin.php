<?php
	
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ZipArchive;

class Admin extends Controller
{
	public $cfg;
    public $style;

    public function __construct()
    {
        $this->cfg = \App\Config::first();
        $this->style = \App\Style::first();
    }
	public function header($title = 'Admin',$area = false)
	{
		$title = $title.' | '.$this->cfg->name;
		$cfg = $this->cfg;
		$tp = url("/themes/".$this->cfg->theme);
		return view('admin/header')->with(compact('title','cfg','tp','area'))->render(); 
	}
	public function footer()
	{
		return view('admin/footer')->render(); 
	}
	public function login()
	{
		if(isset(request()->login)){
			// Check email and password and redirect to the dashboard
			$email = escape(request()->email);
			$password = md5(request()->pass);
			if(empty($email) or empty($password)){
				$error = '<div class="alert alert-danger"> All fields are required </div>';
			} else {
				if(\App\Administrator::where(['email' => $email,'password' => $password])->count() > 0) {
					// Generate a new secure ID for this session and redirect to dashboard
					$secure_id = md5(microtime());
					\App\Administrator::where(['email' => $email])->update(['secure' => $secure_id]);
					session(['admin' => $secure_id]);
					return redirect('admin');
				} else {
					$error = '<div class="alert alert-danger"> Wrong email or password </div>';
				}
			}
		}
		$cfg = $this->cfg;
		$tp = url("/themes/".$this->cfg->theme);
		return view('admin/login')->with(compact('cfg','error','tp'))->render(); 
	}
	public function logout()
	{
		// Clear the admin seesion 
		session(['admin' => '']);
		return redirect('admin/login');
	}
	public function index()
	{
		// Statistics data for the last 7 days
		for ($day = 6; $day >= 0; $day--) {
			$d[7 - $day] = "'".date('Y-m-d', strtotime("-" . $day . " day"))."'";
			$i[date('Y-m-d', strtotime("-" . $day . " day"))] = 0;
			$o[date('Y-m-d', strtotime("-" . $day . " day"))] = 0;
			$s[date('Y-m-d', strtotime("-" . $day . " day"))] = 0;
			$c[date('Y-m-d', strtotime("-" . $day . " day"))] = 0;
		}
		$fs = \App\Visitor::whereRaw('date > '.$d[1])->orderby("date","asc")->get();
		foreach($fs as $visits){
			$i[$visits->date] = $visits->visits;
		}
		$yesterday = date('Y-m-d', strtotime(date('Y-m-d') .' -1 day'));
		$yvisits = (!in_array($yesterday,$i)) ? $i[$yesterday] : 0;
		$tvisits = (!in_array(date('Y-m-d'),$i)) ? $i[date('Y-m-d')] : 0;
		// Order and sales stats
		$order_query = \App\Order::whereRaw('date > '.$d[1])->orderby("date","asc")->get();
		foreach ($order_query as $order){
			$o[$order->date] = $o[$order->date]+1;
			$s[$order->date] = $s[$order->date]+$order->summ;
			$c[$order->date] = $o[$order->date] / $i[$order->date]*100;
		}
		if ($yorders = \App\Order::whereRaw('date = '.$yesterday)->count() > 0){
			$ysales = \App\Order::whereRaw('date = '.$yesterday)->sum('summ');
		} else {
			$ysales = 0;
		}
		$torders = \App\Order::whereRaw('date = '.date('Y-m-d'))->count();
		if ($torders > 0){
			$tsales = \App\Order::whereRaw('date = '.date('Y-m-d'))->sum('summ');
		} else {
			$tsales = 0;
		}
		$yconversion = $c[$yesterday];
		$tconversion = $c[date('Y-m-d')];
		// Charts - Max value
		$mvisits = max($i)+max($i)*2/6+1;
		$morders = max($o)+max($o)*2/6+1;
		$msales = max($s)+max($s)*2/6+1;
		$mconversion = max($c)+max($c)*2/6+1;
		// Charts - difference between yesterday and today in percentage
		$porders = percentage($yorders,$torders);
		$pvisits = percentage($yvisits,$tvisits);
		$psales = percentage($ysales,$tsales);
		$pconversion = percentage($yconversion,$tconversion);
		// Order counts by status
		$stat['0'] = \App\Order::count();
		$stat['1'] = \App\Order::where('stat',1)->count();
		$stat['2'] = \App\Order::where('stat',2)->count();
		$stat['3'] = \App\Order::where('stat',3)->count();
		$stat['4'] = \App\Order::where('stat',4)->count();
		// Email subscribers
		$emails = array();
		$emails['orders'] = \App\Order::count();
		$emails['support'] = \App\Ticket::count();
		$emails['newsletter'] = \App\Subscriber::count();
		// Charts Data
		$chart = array();
		$chart['days'] = implode(', ',$d);
		$i = implode(', ',$i);
		$o = implode(', ',$o);
		$s = implode(', ',$s);
		$c = implode(', ',$c);
		$ssales = \App\Order::sum('summ');
		// Last site activities
		$orders = \App\Order::orderby('id','desc')->limit(3)->get();
		$reviews = \App\Review::orderby('id','desc')->limit(3)->get();
		$tickets = \App\Ticket::orderby('id','desc')->limit(3)->get();
		$referrers = \App\Referrer::orderby('visits','desc')->limit(3)->get();
		$oss = \App\Os::orderby('visits','desc')->limit(3)->get();
		$browsers = \App\Browser::orderby('visits','desc')->limit(3)->get();
		$subscribers = \App\Subscriber::limit(6)->get();
		$countries = \App\Country::orderby('visitors','desc')->orderby("orders",'desc')->limit(10)->get();
		$cfg = $this->cfg;
		$tp = url("/themes/".$this->cfg->theme);
		$header = $this->header('Admin','index');
		$footer = $this->footer();
		return view('admin/index')->with(compact('header','cfg','tp','d','i','o','s','c','stat','porders','pvisits','psales','pconversion','ssales','orders','reviews','tickets','referrers','oss','browsers','emails','subscribers','countries','chart','morders','mvisits','mconversion','msales','footer'));
	}
	public function map(){
		$json = array();
		// return visitor count and orders by country
		$countries = \App\Country::get();
		foreach ($countries as $country) {
			$json[strtolower($country->iso)] = array(
				'total'  => $country->orders,
				'visitors'  => $country->visitors,
			);
		}
		return json_encode($json);
	}
	public function products($action = 'list',$action_id = 0)
	{
		if(isset(request()->add)){
			$data['title'] = request()->title;
			$data['text'] = request()->text;
			$data['short_text'] = request()->short_text;
			$data['price'] = request()->price;
			$data['category'] = (int)request()->category;
			$data['quantity'] = (int)request()->quantity;
			$data['images'] = '';
			$data['download'] = '';
			$options = array();
			if (isset(request()->option_title)){
				$choice_titles = request()->option_title;
				$choice_types = request()->option_type;
				$choice_no = request()->option_no;
				if(count($choice_titles ) > 0){
					foreach ($choice_titles as $i => $row) {
						$choice_options = request()->input('option_set'.$choice_no[$i]);
						$options[] = array(
										'no' => $choice_no[$i],
										'title' => $choice_titles[$i],
										'name' => 'choice_'.$choice_no[$i],
										'type' => $choice_types[$i],
										'option' => $choice_options
									);
					}
				}
			}
            $data['options'] = json_encode($options);
			$product = \App\Product::insertGetId($data);
				if (request()->file('images')) {
					// Upload selected images to product assets directory
					$order = 0;
					$images = array();
					foreach (request()->file('images') as $file) {
						$name = $file->getClientOriginalName();
						if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
							$images[] = $image = $product.'-'.$order.'.'.$file->getClientOriginalExtension();
							$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'products';
							$file->move($path,$image);
							$order++;
						} else {
							notices("warning","$name is not a valid format");
						}
					}
					\App\Product::where(["id" => $product])->update(["images" => implode(',',$images)]);
				}
				if (request()->file('download')) {
					// Upload the downloadable file to product downloads directory
					$name = request()->file('download')->getClientOriginalName();
					$file = md5(time()).'.'.request()->file('download')->getClientOriginalExtension();
					$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'downloads';
					request()->file('download')->move($path,$file);
					\App\Product::where(["id" => $product])->update(["download" => $file]);
				}
			notices("success","Product has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['title'] = request()->title;
			$data['text'] = request()->text;
			$data['price'] = request()->price;
			$data['category'] = (int)request()->category;
			$data['quantity'] = (int)request()->quantity;
			$options = array();
			if (isset(request()->option_title)){
				$choice_titles = request()->option_title;
				$choice_types = request()->option_type;
				$choice_no = request()->option_no;
				if(count($choice_titles ) > 0){
					foreach ($choice_titles as $i => $row) {
						$choice_options = request()->input('option_set'.$choice_no[$i]);
						$options[] = array(
										'no' => $choice_no[$i],
										'title' => $choice_titles[$i],
										'name' => 'choice_'.$choice_no[$i],
										'type' => $choice_types[$i],
										'option' => $choice_options
									);
					}
				}
			}
            $data['options'] = json_encode($options);
			\App\Product::where(["id" => $action_id])->update($data);
				if (request()->file('images')) {
					// Update product images
					$order = 0;
					$images = array();
					foreach (request()->file('images') as $file) {
						$name = $file->getClientOriginalName();
						if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
							$images[] = $image = $action_id.'-'.$order.'.'.$file->getClientOriginalExtension();
							$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'products';
							$file->move($path,$image);
							$order++;
						} else {
							notices("warning","$name is not a valid format");
						}
					}
					\App\Product::where(["id" => $action_id])->update(["images" => implode(',',$images)]);
				}
				if (request()->file('download')) {
					// Update the downloadable file
					$name = request()->file('download')->getClientOriginalName();
					$file = md5(time()).'.'.request()->file('download')->getClientOriginalExtension();
					$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'downloads';
					request()->file('download')->move($path,$file);
					\App\Product::where(["id" => $action_id])->update(["download" => $file]);
				}
				notices("success","Product has been updated successfully !");
		}
		if($action == "delete"){
			\App\Product::where(["id" => $action_id])->delete();
			notices("success","Product has been deleted successfully !");
		}
		if($action == "edit"){
			$product = \App\Product::where(["id" => $action_id])->first();
		}
		$header = $this->header('Products','products');
		$products = \App\Product::orderby('id','desc')->get();
		$categories = \App\Category::where(["parent" => 0])->get();
		$footer = $this->footer();
		return view('admin/products')->with(compact('header','action','products','product','categories','footer'));
	}
	public function categories($action = 'list',$action_id = 0){
		if(isset(request()->add)){
			$data['name'] = request()->name;
			$data['path'] = request()->path;
			$data['parent'] = request()->parent;
			\App\Category::insert($data);
			notices("success","Category has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['name'] = request()->name;
			$data['path'] = request()->path;
			$data['parent'] = request()->parent;
			\App\Category::where(["id" => $action_id])->update($data);
			notices("success","Category has been edited successfully !");
		}
		if($action == "delete")
		{
			\App\Category::where(["id" => $action_id])->delete();
			notices("success","Category has been deleted successfully !");
		}
		if($action == "edit") {
			$category = \App\Category::where(["id" => $action_id])->first();
		}
		$header = $this->header('Categories','categories');
		$categories = \App\Category::orderby('id','desc')->get();
		$footer = $this->footer();
		$parents = \App\Category::where(["parent" => 0])->get();
		return view('admin/categories')->with(compact('header','action','categories','parents','category','footer'));
	}
	public function pages($action = 'list',$action_id = 0){
		if(isset(request()->add)){
			$data['title'] = request()->title;
			$data['content'] = request()->content;
			$data['path'] = request()->path;
			\App\Page::insert($data);
			notices("success","Page has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['title'] = request()->title;
			$data['content'] = request()->content;
			$data['path'] = request()->path;
			\App\Page::where(["id" => $action_id])->update($data);
			notices("success","Category has been edited successfully !");
		}
		if($action == "delete")
		{
			\App\Page::where(["id" => $action_id])->delete();
			notices("success","Category has been deleted successfully !");
		}
		if($action == "edit") {
			$page = \App\Page::where(["id" => $action_id])->first();
		}
		$header = $this->header('Pages','pages');
		$pages = \App\Page::orderby('id','desc')->get();
		$tp = url("/themes/".$this->cfg->theme);
		$footer = $this->footer();
		return view('admin/pages')->with(compact('header','action','pages','page','tp','footer'));
	}
	public function blog($action = 'list',$action_id = 0 ){
		if(isset(request()->add)){
			$data['title'] = request()->title;
			$data['content'] = request()->content;
			$data['time'] = time();
			$data['images'] = '';
			$post = \App\Post::insertGetId($data);
				if (request()->file('image')) {
					// Upload blog post image to blog assets directory
					$file = request()->file('image');
					$name = $file->getClientOriginalName();
					if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
						$image = $post.'.'.$file->getClientOriginalExtension();
						$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'blog';
						$file->move($path,$image);
					} else {
						notices("warning","$name is not a valid format");
					}
					\App\Post::where(["id" => $post])->update(["images" => $image]);
				}
			notices("success","Post has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['title'] = request()->title;
			$data['content'] = request()->content;
			\App\Post::where(["id" => $action_id])->update($data);
				if (request()->file('image')) {
					// Update the blog post image
					$file = request()->file('image');
					$name = $file->getClientOriginalName();
					if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
						$image = $action_id.'.'.$file->getClientOriginalExtension();
						$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'blog';
						$file->move($path,$image);
					} else {
						notices("warning","$name is not a valid format");
					}
					\App\Post::where(["id" => $action_id])->update(["images" => $image]);
				}
			notices("success","Post has been updated successfully !");
		}
		if($action == "delete")
		{
			\App\Post::where(["id" => $action_id])->delete();
			notices("success","Post has been deleted successfully !");
		}
		if($action == "edit") {
			$post = \App\Post::where(["id" => $action_id])->first();
		}
		$header = $this->header('Blog','blog');
		$posts = \App\Post::get();
		$footer = $this->footer();
		return view('admin/blog')->with(compact('header','action','posts','post','footer'));
	}
	public function customers($action = 'list',$action_id = 0 ){
		if($action == "delete")
		{
			\App\Customer::where(["id" => $action_id])->delete();
			notices("success","Customer has been deleted successfully !");
		}
		$header = $this->header('Customers','customers');
		$customers = \App\Customer::get();
		$footer = $this->footer();
		return view('admin/customers')->with(compact('header','action','customers','footer'));
	}
	public function coupons($action = 'list',$action_id = 0 ){
		if(isset(request()->add)){
			$data['code'] = request()->code;
			$data['discount'] = (int)request()->discount;
			$data['type'] = request()->type;
			\App\Coupon::insert($data);
			notices("success","Coupon has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['code'] = request()->code;
			$data['discount'] = (int)request()->discount;
			$data['type'] = request()->type;
			\App\Coupon::where(["id" => $action_id])->update($data);
			notices("success","Coupon has been updated successfully !");
		}
		if($action == "delete")
		{
			\App\Coupon::where(["id" => $action_id])->delete();
			notices("success","Coupon has been deleted successfully !");
		}
		if($action == "edit") {
			$coupon = \App\Coupon::where(["id" => $action_id])->first();
		}
		$header = $this->header('Coupons','coupons');
		$coupons = \App\Coupon::get();
		$footer = $this->footer();
		return view('admin/coupons')->with(compact('header','action','coupons','coupon','footer'));
	}
	public function shipping($action = 'list',$action_id = 0 ){
		if(isset(request()->add)){
			$data['country'] = request()->country;
			$data['cost'] = request()->cost;
			\App\Shipping::insert($data);
			notices("success","Shipping cost has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['country'] = request()->country;
			$data['cost'] = request()->cost;
			\App\Shipping::where(["id" => $action_id])->update($data);
			notices("success","Shipping cost has been edited successfully !");
		}
		if($action == "delete")
		{
			\App\Shipping::where(["id" => $action_id])->delete();
			notices("success","Shipping cost has been deleted successfully !");
		}
		if($action == "edit") {
			$cost = \App\Shipping::where(["id" => $action_id])->first();
		}
		$header = $this->header('Shipping cost','shipping');
		$costs = \App\Shipping::get();
		$countries = \App\Country::orderby('nicename','asc')->get();
		$footer = $this->footer();
		return view('admin/shipping')->with(compact('header','action','costs','countries','cost','footer'));
	}
	public function reviews($action = 'list',$action_id = 0){
		if($action == 'approve')
		{
			\App\Review::where(["id" => $action_id])->update(["active" => 1]);
			notices("success","Review has been approved !");
		}
		$header = $this->header('Admin','reviews');
		$reviews = \App\Review::get();
		$footer = $this->footer();
		return view('admin/reviews')->with(compact('header','reviews','footer'));
	}
	public function orders($action = 'list',$action_id = 0){
		if($action == 'delete')
		{
			\App\Order::where(["id" => $action_id])->delete();
			notices("success","Order has been deleted successfully !");
		}
		$header = $this->header('Orders','orders');
		$fields = \App\Field::orderby('id','desc')->get();
		$orders = \App\Order::orderby('id','desc')->get();
		$status_array = config('app.statuses');
		if($action == 'details') {
			if(isset(request()->save)){
				\App\Order::where(["id" => $action_id])->update(["stat" => request()->stat]);
				notices("success","Order status has been changed successfully !");
			}
			$order = \App\Order::where(["id" => $action_id])->first();
		}
		$footer = $this->footer();
		return view('admin/orders')->with(compact('header','action','fields','orders','status_array','order','footer'));
	}
    
    
    public function rfqs($action = 'list',$action_id = 0){
		if($action == 'delete')
		{
			\App\Rfq::where(["id" => $action_id])->delete();
			notices("success","RFQ has been deleted successfully !");
		}
		$header = $this->header('RFQs','rfqs');
		$fields = \App\Field::orderby('id','desc')->get();
		$rfqs = \App\Rfq::orderby('id','desc')->get();
		$status_array = config('app.statuses');
		if($action == 'details') {
			if(isset(request()->save)){
				\App\Rfq::where(["id" => $action_id])->update(["stat" => request()->stat]);
				notices("success","RFQ status has been changed successfully !");
			}
			$rfq = \App\Rfq::where(["id" => $action_id])->first();
		}
		$footer = $this->footer();
		return view('admin/rfqs')->with(compact('header','action','fields','rfqs','status_array','rfq','footer'));
	}
    
    
    
	public function statistics($term = 'week'){
		// Create default statistics by selected period
		if ($term == 'year'){
			for ($iDay = 365; $iDay >= 0; $iDay--) {
				$d[366 - $iDay] = "'".date('Y-m-d', strtotime("-" . $iDay . " day"))."'";
				$i[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$o[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$s[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$c[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
			}
		} elseif ($term == 'month'){
			for ($iDay = 30; $iDay >= 0; $iDay--) {
				$d[31 - $iDay] = "'".date('Y-m-d', strtotime("-" . $iDay . " day"))."'";
				$i[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$o[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$s[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$c[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
			}
		} else {
			for ($iDay = 6; $iDay >= 0; $iDay--) {
				$d[7 - $iDay] = "'".date('Y-m-d', strtotime("-" . $iDay . " day"))."'";
				$i[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$o[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$s[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
				$c[date('Y-m-d', strtotime("-" . $iDay . " day"))] = 0;
			}
		}
		
		// Visitors statistics
		$fs = \App\Visitor::whereRaw('date > '.$d[1])->orderby("date","asc")->get();
		$visit = array();
		foreach ($fs as $visits){
			$i[$visits->date] = $visits->visits;
		}
		$yesterday = date('Y-m-d', strtotime(date('Y-m-d') .' -1 day'));
		$yvisits = (!in_array($yesterday,$i)) ? $i[$yesterday] : 0;
		$tvisits = (!in_array(date('Y-m-d'),$i)) ? $i[date('Y-m-d')] : 0;
		
		// Order and sales statistics
		$order_query = \App\Order::whereRaw('date > '.$d[1])->orderby('date','asc')->get();
		foreach ($order_query as $order){
			$o[$order->date] = $o[$order->date]+1;
			$s[$order->date] = $s[$order->date]+$order->summ;
			$c[$order->date] = $o[$order->date] / $i[$order->date]*100;
		}
		if ($yorders = \App\Order::whereRaw('date = '.$yesterday)->count() > 0){
			$ysales = \App\Order::whereRaw('date = '.$yesterday)->sum('summ');
		} else {
			$ysales = 0;
		}
		$torders = \App\Order::whereRaw('date = '.date('Y-m-d'))->count();
		if ($torders > 0){
			$tsales = \App\Order::whereRaw('date = '.date('Y-m-d'))->sum('summ');
		} else {
			$tsales = 0;
		}
		$yconversion = $c[$yesterday];
		$tconversion = $c[date('Y-m-d')];
		// Charts - Max value
		$morders = max($o)+max($o)*2/6+1;
		$msales = max($s)+max($s)*2/6+1;
		$mconversion = max($c)+max($c)*2/6+1;
		$mvisits = max($i)+max($i)*2/6+1;
		// Charts - difference between yesterday and today in percentage
		$porders = percentage($yorders,$torders);
		$pvisits = percentage($yvisits,$tvisits);
		$psales = percentage($ysales,$tsales);
		$pconversion = percentage($yconversion,$tconversion);
		// Charts Data
		$orders = \App\Order::count();
		$ssales = \App\Order::sum('summ');
		$chart = array();
		$chart['days'] = implode(', ',$d);
		$i = implode(', ',$i);
		$o = implode(', ',$o);
		$s = implode(', ',$s);
		$c = implode(', ',$c);
		$header = $this->header('Statistics','statistics');
		$cfg = $this->cfg;
		$footer = $this->footer();
		return view('admin/statistics')->with(compact('header','term','cfg','i','o','s','c','stat','porders','pvisits','psales','pconversion','orders','ssales','chart','morders','mvisits','mconversion','msales','footer'));
	}
	public function tracking($action = 'list',$action_id = 0 ){
		if(isset(request()->add)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			\App\Tracking::insert($data);
			notices("success","Tracking code has been added successfully !");
		}
		if($action == 'delete')
		{
			\App\Tracking::where(["id" => $action_id])->delete();
			notices("success","Tracking code has been deleted successfully !");
		}
		$header = $this->header('Tracking','tracking');
		$codes = \App\Tracking::orderby('id','desc')->get();
		$footer = $this->footer();
		return view('admin/tracking')->with(compact('header','action','codes','footer'));
	}
	public function newsletter(){
		if(isset(request()->send)){
			$emails['orders'] = array();
			$emails['support'] = array();
			$emails['newsletter'] = array();
			$orders = \App\Order::get();
			foreach ($orders as $order){
				$emails['orders'][$order->email] = $order->email;
			}
			$tickets = \App\Ticket::get();
			foreach ($tickets as $ticket){
				$emails['support'][$ticket->email] = $ticket->email;
			}
			$subscribers = \App\Subscriber::get();
			foreach ($subscribers as $subscriber){
				$emails['newsletter'][$subscriber->email] = $subscriber->email;
			}
			if (request()->group == 'orders') {
				$tos = $emails['orders'];
			} elseif (request()->group == 'newsletter') {
				$tos = $emails['newsletter'];
			} elseif (request()->group == 'support') {
				$tos = $emails['support'];
			} else {
				$tos = array_merge($emails['support'],$emails['newsletter'],$emails['orders']);
			}
			// Send email to every email in the slected group
			foreach ($tos as $to){
				mailing('newsletter',array('title'=>request()->title,'email'=>$to,'content'=>nl2br(request()->content)),request()->title,$to);
			}
			notices("success","Newsletter has been sent successfully !");
		}
		$header = $this->header('Newsletter','newsletter');
		$footer = $this->footer();
		return view('admin/newsletter')->with(compact('header','footer'));
	}
	public function referrers(){
		$header = $this->header('Referrers','referrers');
		$referrers = \App\Referrer::orderby('visits','desc')->get();
		$footer = $this->footer();
		return view('admin/referrers')->with(compact('header','referrers','footer'));
	}
	public function os(){
		$header = $this->header('Operating systems','os');
		$OSs = \App\Os::orderby('visits','desc')->get();
		$footer = $this->footer();
		return view('admin/os')->with(compact('header','OSs','footer'));
	}
	public function browsers(){
		$header = $this->header('Browsers','browsers');
		$browsers = \App\Browser::orderby('visits','desc')->get();
		$footer = $this->footer();
		return view('admin/browsers')->with(compact('header','browsers','footer'));
	}
	public function payment($action = 'list',$action_id = 0 ){
		if(isset(request()->edit)){
			$method = \App\Payment::where(["id" => $action_id])->first();
			$method_options = json_decode($method->options,true);
			$options = array();
			foreach ($method_options as $key => $value){
				$options[$key] = request()->$key;
			}
			$data_options = json_encode($options);
			$data['title'] = request()->title;
			$data['active'] = request()->active;
			$data['options'] = $data_options;
			\App\Payment::where(["id" => $action_id])->update($data);
			notices("success","Payment method has been updated successfully !");
		}
		$header = $this->header('Payment methods','payment');
		$methods = \App\Payment::get();
		if($action == "edit") {
			$method = \App\Payment::where(["id" => $action_id])->first();
		}
		$footer = $this->footer();
		return view('admin/payment')->with(compact('header','action','methods','method','footer'));
	}
	public function currency($action = 'list',$action_id = 0 ){
		if(isset(request()->add)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			$data['rate'] = request()->rate;
			\App\Currency::insert($data);
			notices("success","Currency has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			$data['rate'] = request()->rate;
			\App\Currency::where(["id" => $action_id])->update($data);
			notices("success","Currency has been updated successfully !");
		}
		if($action == "delete")
		{
			\App\Currency::where(["id" => $action_id])->delete();
			notices("success","Currency has been deleted successfully !");
		}
		if($action == "default")
		{
			\App\Currency::where(["default" => 1])->update(["default" => 0]);
			\App\Currency::where(["id" => $action_id])->update(["default" => 1]);
			notices("success","Currency has been set as default !");
		}
		$header = $this->header('Currency','currency');
		$currencies = \App\Currency::get();
		if($action == "edit") {
			$currency = \App\Currency::where(["id" => $action_id])->first();
		}
		$footer = $this->footer();
		return view('admin/currency')->with(compact('header','action','currencies','currency','footer'));
	}
	public function settings(){
		if(isset(request()->save)){
			if (request()->file('logo_upload')) {
				// Upload the logo to the assets directory
				$file = request()->file('logo_upload');
				if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
					$image = 'logo.'.$file->getClientOriginalExtension();
					$path = base_path().DIRECTORY_SEPARATOR.'assets';
					$file->move($path,$image);
					$_POST['logo'] = 'assets/'.$image;
				} else {
					notices("warning",$file->getClientOriginalExtension()." is not a valid format");
				}
			}
			unset($_POST['save'],$_POST['_token'],$_POST['logo_upload']);
			DB::table('config')->update($_POST);
			notices("success","Settings have been updated successfully !");
		}
		$header = $this->header('Settings','settings');
		$languages = \App\Language::get();
		$cfg = $this->cfg;
		$footer = $this->footer();
		return view('admin/settings')->with(compact('header','languages','cfg','footer'));
	}
	public function theme(){
		if(isset(request()->save)){
			unset($_POST['save'],$_POST['_token']);
			$_POST['background'] = $_POST['color1'].','.$_POST['color2'];
			$_POST['button'] = $_POST['button_text'].','.$_POST['button_link'];
			unset($_POST['color1'],$_POST['color2'],$_POST['button_text'],$_POST['button_link']);
			DB::table('style')->update($_POST);
			notices("success","Style settings have been deleted successfully !");
		}
		$header = $this->header('Theme settings','theme');
		$style = $this->style;
		$footer = $this->footer();
		return view('admin/theme')->with(compact('header','style','footer'));
	}
	public function languages($action = 'list',$action_id = 0){
		if (isset(request()->language)){
			// Use requested language
			$l = request()->language;
		} else {
			// Use default language
			$l = $this->cfg->lang;
		}
		if($action == "save") {
			// Saving new translation
			\App\Translation::where(["id" => $action_id])->update(["translation" => request()->translation]);
			return "success";
		}
		if(isset(request()->add)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			\App\Language::insert($data);
			notices("success","Language has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			\App\Language::where(["id" => $action_id])->update($data);
			notices("success","Language has been updated successfully !");
		}
		if($action == "delete")
		{
			// Delete translation from database
			\App\Translation::where(["id" => $action_id])->delete();
			notices("success","Translation has been deleted successfully !");
		}
		if($action == "delete_language") {
			// Delete lang from database
			\App\Language::where(["id" => $action_id])->delete();
			notices("success","Language has been deleted successfully !");
		}
		if($action == "edit"){
			$lang = \App\Language::where(["id" => $action_id])->first();
		}
		$header = $this->header('Language','lang');
		$langs = \App\Language::get();
		$translations = \App\Translation::where(["lang" => $l])->get();
		$footer = $this->footer();
		return view('admin/languages')->with(compact('header','action','l','langs','translations','lang','footer'));
	}
	public function tokens($action = 'list',$action_id = 0){
		if($action == "add"){
			\App\Token::insert(["token" => md5(time()),"requests" => 0]);
			notices("success","The API token has been generated successfully !");
		}
		if($action == "delete")
		{
			\App\Token::where(["token" => $action_id])->delete();
			notices("success","Token has been deleted successfully !");
		}
		$header = $this->header('API Tokens','tokens');
		$tokens = \App\Token::get();
		$footer = $this->footer();
		return view('admin/tokens')->with(compact('header','tokens','footer'));
	}
	public function export($action = 'list'){
		if($action == 'database')
		{
			// we'll try to dump the database
			exec(sprintf('mysqldump --force --compress --disable-keys  --user=%s --password=%s --host=%s %s > %s',escapeshellarg(env('DB_USERNAME')),escapeshellarg(env('DB_PASSWORD')),escapeshellarg(env('DB_HOST', '127.0.0.1')),escapeshellarg(env('DB_DATABASE')),escapeshellarg('backup.sql')),$dumpResult, $result);
			if ($result == 1){
				// we'll use full path if you're using windows , ex : C:\xampp\mysql\bin\mysqldump.exe
				exec(sprintf('C:\xampp\mysql\bin\mysqldump.exe --force --compress --disable-keys  --user=%s --password=%s --host=%s %s > %s',escapeshellarg(env('DB_USERNAME')),escapeshellarg(env('DB_PASSWORD')),escapeshellarg(env('DB_HOST', '127.0.0.1')),escapeshellarg(env('DB_DATABASE')),escapeshellarg('backup.sql')),$dumpResult, $result);
				if ($result == 1){
					return 'Database backup failed , try to change the path to the mysqldump file in line 789 in the Admin controller';
				}
			}
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
			header( "Content-Description: File Transfer" );
			header( "Content-type: application/sql" );
			header( "Content-Disposition: attachment; filename=\"backup.sql\"" );
			header( "Content-Transfer-Encoding: binary" );
			// Download file and delete it
			readfile( base_path('backup.sql') );
			unlink( base_path('backup.sql') );
			return;
		}
		elseif ($action == 'files') {
			$rootPath = base_path();
			$backup = md5(time()).'.zip';
			// Initialize archive object
			$zip = new ZipArchive();
			$zip->open($backup, ZipArchive::CREATE | ZipArchive::OVERWRITE);
			
			// Create recursive directory iterator
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath),RecursiveIteratorIterator::LEAVES_ONLY);
			
			foreach ($files as $name => $file)
			{
				// Skip directories (they would be added automatically)
				if (!$file->isDir())
				{
					// Get real and relative path for current file
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($rootPath) + 1);
					
					// Add current file to archive
					$zip->addFile($filePath, $relativePath);
				}
			}
			
			// Zip archive will be created only after closing object
			$zip->close();
			
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
			header( "Content-Description: File Transfer" );
			header( "Content-type: application/zip" );
			header( "Content-Disposition: attachment; filename=\"" . $backup . "\"" );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Length: " . filesize( $backup ) );
			
			ob_get_clean();
			readfile( $backup );
			ob_get_clean();
			unlink($backup);
			return;
		}
		$header = $this->header('Export','export');
		$footer = $this->footer();
		return view('admin/export')->with(compact('header','footer'));
	}
	public function slider($action = 'list',$action_id = 0){
		if(isset(request()->add)){
			$data['title'] = request()->title;
			$data['link'] = request()->link;
			$data['image'] = '';
			$slide = \App\Slide::insertGetId($data);
			if (request()->file('image')) {
				$file = request()->file('image');
				$name = $file->getClientOriginalName();
				if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
					$image = $slide.'.'.$file->getClientOriginalExtension();
					$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'slider';
					$file->move($path,$image);
					\App\Slide::where(["id" => $slide])->update(["image" => $image]);
				} else {
					notices("warning","$name is not a valid format");
				}
			}
			notices("success","The slide has been added successfully !");
		}
		if(isset(request()->edit)){
			\App\Slide::where(["id" => $action_id])->update(["title" => request()->title,"link" => request()->link]);
			if (request()->file('image')) {
				$file = request()->file('image');
				$name = $file->getClientOriginalName();
				if (in_array($file->getClientOriginalExtension(), array("jpg", "png", "gif", "bmp"))){
					$image = $action_id.'.'.$file->getClientOriginalExtension();
					$path = base_path().DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'slider';
					$file->move($path,$image);
				} else {
					notices("warning","$name is not a valid format");
				}
				\App\Slide::where(["id" => $action_id])->update(["image" => $image]);
			}
			notices("success","The slide has been updated successfully !");
		}
		if($action == 'delete')
		{
			\App\Slide::where(["id" => $action_id])->delete();
			notices("success","The slide has been deleted successfully !");
		}
		$header = $this->header('Slider','slider');
		$slides = \App\Slide::orderby('id','desc')->get();
		if($action == 'edit') {
			$slide = \App\Slide::where(["id" => $action_id])->first();
		}
		$footer = $this->footer();
		return view('admin/slider')->with(compact('header','action','slides','slide','footer'));
	}
	public function editor($file = 'index.php'){
		$cfg = $this->cfg;
		if (isset($file)){
			$file = resource_path('views/'.$file);
			if (!file_exists($file)){
				$file = resource_path('views/index.php');
				notices("warning","File not found , edititng index.php ");
			}
		} else {
			$file = resource_path('views/index.php');
		}
		
		if (isset(request()->text))
		{
			// Save the new content
			file_put_contents($file, request()->text);
			notices("success","The file has been saved successfully !");
		}
		
		// read the file
		$text = file_get_contents($file);
		$header = $this->header('Editor','editor');
		$files = glob(resource_path('views/*.php'), GLOB_BRACE);
		$footer = $this->footer();
		return view('admin/editor')->with(compact('header','cfg','files','text','footer'));
	}
	public function templates($action = 'list',$action_id = 0){
		if(isset(request()->edit)){
			$data['title'] = request()->title;
			$data['template'] = request()->template;
			\App\Template::where(["id" => $action_id])->update($data);
			notices("success","Template has been updated successfully !");
		}
		if($action == 'edit')
		{
			$template = \App\Template::where(["id" => $action_id])->first();
		}
		$header = $this->header('Templates','templates');
		$templates = \App\Template::orderby('id','desc')->get();
		$footer = $this->footer();
		return view('admin/templates')->with(compact('header','action','template','templates','footer'));
	}
	public function builder($action = 'list',$action_id = 0){
		if ($action == 'page') {
			$area = 'page';
		} elseif ($action == 'post') {
			$area = 'post';
		} else {
			$area = 'home';
		}
		if($action == 'save'){
			// Save the new order of items
			$data = request()->data;
			parse_str($data,$str);
			$builder = $str['item'];
			foreach($builder as $key => $value){
				$key=$key+1;
				\App\Bloc::where(["id" => $value])->update(['o' => $key]);
			}
			return "Succesfully updated";
		}
		if(isset(request()->add)){
			$data['area'] = request()->area;
			$data['content'] = request()->content;
			$data['title'] = request()->title;
			\App\Bloc::insert($data);
			notices("success","The bloc has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['content'] = request()->content;
			$data['title'] = request()->title;
			\App\Bloc::where(["id" => $action_id])->update($data);
			notices("success","The bloc has been updated successfully !");
		}
		if($action == 'edit') {
			$bloc =	\App\Bloc::where(["id" => $action_id])->first();
		}
		if($action == 'delete')
		{
			\App\Bloc::where(["id" => $action_id])->delete();
			notices("success","The bloc has been deleted successfully !");
		}
		$header = $this->header('Page builder','builder');
		$blocs = \App\Bloc::where('area',$area)->get();
		$tp = url("/themes/".$this->cfg->theme);
		$footer = $this->footer();
		return view('admin/builder')->with(compact('header','action','bloc','blocs','tp','footer'));
	}
	public function menu($action = 'list',$action_id = 0){
		if($action == 'save'){
			$data = request()->data;
			parse_str($data,$str);
			$builder = $str['item'];
			foreach($builder as $key => $value){
				$key=$key+1;
				\App\Menu::where(["id" => $value])->update(['o' => $key]);
			}
			return "Succesfully updated";
		}
		if(isset(request()->add)){
			$data['link'] = request()->link;
			$data['title'] = request()->title;
			$data['parent'] = request()->parent;
			\App\Menu::insert($data);
			notices("success","The menu item has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['link'] = request()->link;
			$data['title'] = request()->title;
			$data['parent'] = request()->parent;
			\App\Menu::where(["id" => $action_id])->update($data);
			notices("success","The menu item has been updated successfully !");
		}
		if($action == 'edit') {
			$item = \App\Menu::where(["id" => $action_id])->first();
		}
		if($action == 'delete')
		{
			\App\Menu::where(["id" => $action_id])->delete();
			notices("success","The menu item has been deleted successfully !");
		}
		$header = $this->header('Menu','menu');
		$items = \App\Menu::orderby('o','asc')->get();
		$parents = \App\Menu::where(['parent' => 0])->get();
		$tp = url("/themes/".$this->cfg->theme);
		$footer = $this->footer();
		return view('admin/menu')->with(compact('header','action','item','items','tp','parents','footer'));
	}
	public function bottom($action = 'list',$action_id = 0){
		if($action == 'save'){
			$data = request()->data;
			parse_str($data,$str);
			$builder = $str['item'];
			foreach($builder as $key => $value){
				$key=$key+1;
				\App\Footer::where(["id" => $value])->update(['o' => $key]);
			}
			return "Succesfully updated";
		}
		if(isset(request()->add)){
			$data['link'] = request()->link;
			$data['title'] = request()->title;
			\App\Footer::insert($data);
			notices("success","The menu item has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['link'] = request()->link;
			$data['title'] = request()->title;
			\App\Footer::where(["id" => $action_id])->update($data);
			notices("success","The menu item has been updated successfully !");
		}
		if($action == 'edit') {
			$item = \App\Footer::where(["id" => $action_id])->first();
		}
		if($action == 'delete')
		{
			\App\Footer::where(["id" => $action_id])->delete();
			notices("success","The menu item has been deleted successfully !");
		}
		$header = $this->header('Footer menu','bottom');
		$items = \App\Footer::orderby('o','asc')->get();
		$tp = url("/themes/".$this->cfg->theme);
		$footer = $this->footer();
		return view('admin/bottom')->with(compact('header','action','item','items','tp','footer'));
	}
	
	public function fields($action = 'list',$action_id = 0){
		if(isset(request()->add)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			DB::statement(DB::raw("ALTER TABLE `orders` ADD `".$data['code']."` VARCHAR(255) NOT NULL"));
			\App\Field::insert($data);
			notices("success","Field has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['name'] = request()->name;
			$data['code'] = request()->code;
			$field = \App\Field::where(["id" => $action_id])->first();
			DB::statement(DB::raw("ALTER TABLE `orders` CHANGE `".$field->code."` `".$data['code']."` VARCHAR(255) NOT NULL"));
			\App\Field::where(["id" => $action_id])->update($data);
			notices("success","Field has been updated successfully !");
		}
		if($action == 'delete')
		{
			// Delete field from database
			$field = \App\Field::where(["id" => $action_id])->first();
			DB::statement(DB::raw("ALTER TABLE `orders` DROP `".$field->code."`"));
			\App\Field::where(["id" => $action_id])->delete();
			notices("success","Field has been deleted successfully !");
		}
		if($action == 'edit')
		{
			$field = \App\Field::where(["id" => $action_id])->first();
		}
		$header = $this->header('Extrafields','fields');
		$fields = \App\Field::get();
		$footer = $this->footer();
		return view('admin/fields')->with(compact('header','action','field','fields','footer'));
	}
	public function support($action = 'list',$action_id = 0){
		if(isset(request()->send)){
			// Send E-mail to customer
			$ticket = \App\Ticket::where(["id" => $action_id])->first();
			mailing('reply',array('title' => request()->title,'email'=>$ticket->email,'reply'=>nl2br(request()->reply)),request()->title,$ticket->email);
			notices("success","E-mail has been sent successfully !");
		}
		if($action == 'reply'){
			$ticket = \App\Ticket::where(["id" => $action_id])->first();
		}
		if($action == 'delete')
		{
			\App\Ticket::where(["id" => $action_id])->delete();
			notices("success","Ticket has been deleted successfully !");
		}
		$header = $this->header('Support','support');
		$tickets = \App\Ticket::orderby('id','desc')->get();
		$footer = $this->footer();
		return view('admin/support')->with(compact('header','action','ticket','tickets','footer'));
	}
	public function administrators($action = 'list',$action_id = 0){
		if(isset(request()->add)){
			$data['name'] = request()->name;
			$data['email'] = request()->email;
			$data['password'] = md5(request()->pass);
			$data['secure'] = md5(time());
			\App\Administrator::insert($data);
			notices("success","Admin has been added successfully !");
		}
		if(isset(request()->edit)){
			$data['name'] = request()->name;
			$data['email'] = request()->email;
			$data['password'] = md5(request()->pass);
			\App\Administrator::where(["id" => $action_id])->update($data);
			notices("success","Admin details has been updated successfully !");
		}
		if($action == 'delete')
		{
			\App\Administrator::where(["id" => $action_id])->delete();
			notices("success","Admin has been deleted successfully !");
		}
		if($action == 'edit'){
			$admin = \App\Administrator::where(["id" => $action_id])->first();
		}
		$header = $this->header('Administrators','administrators');
		$admins = \App\Administrator::orderby('id','desc')->get();
		$footer = $this->footer();
		return view('admin/administrators')->with(compact('header','action','admin','admins','footer'));
	}
	public function profile(){
		if(isset(request()->update)){
			$user = \App\Administrator::where(["secure" => session('admin')])->first();
			$data['name'] = request()->name;
			$data['email'] = request()->email;
			if (request()->pass != ""){
				$data['password'] = md5(request()->pass);
			} else {
				$data['password'] = $user->password;
			}
			\App\Administrator::where(["secure" => session('admin')])->update($data);
			notices("success","Profile has been updated successfully !");
		}
		$header = $this->header('Profile','profile');
		$user = \App\Administrator::where(["secure" => session('admin')])->first();
		$footer = $this->footer();
		return view('admin/profile')->with(compact('header','user','footer'));
	}
}