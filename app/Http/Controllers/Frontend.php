<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Frontend extends Controller
{
	public $cfg;
	public $style;
	public $menu;
	public $footer;
	public $languages;
	public $categories;

	public function __construct()
	{
		// Check if installed by trying to connect to database
		try {
			DB::connection()->getPdo();
		} catch (\Exception $e) {
			// redirect to the installer
			return redirect('install')->send();
		}
		$this->cfg = \App\Config::first();
		$this->style = \App\Style::first();
		$this->menu = \App\Menu::where('parent',0)->orderby('o','asc')->get();
		$this->footer = \App\Footer::orderby('o','asc')->get();
		$this->languages = \App\Language::orderby('id','asc')->get();
		$this->categories = \App\Category::where('parent',0)->orderby('id','asc')->get();
		$this->currencies = \App\Currency::orderby('default','asc')->get();
	}
	public function header($title = false,$desc = false,$page = false,$landing = false,$bg = false)
	{
		if (!empty(session('customer'))){
			if(DB::select("SELECT COUNT(*) as count FROM customers WHERE sid = '".session('customer')."' ")[0]->count == 0){
				session(['customer' => '']);
			}
		}
		// Update visitors count
		\App\Config::first()->increment('views');
		$visit =  \App\Visitor::where('date',date('Y-m-d'))->count();
		// Update today visitors
		if ($visit > 0)
		{
			\App\Visitor::where('date',date('Y-m-d'))->increment('visits');
		} else {
			\App\Visitor::insert(['date' => date('Y-m-d'),'visits' => '1']);
		}
		// Get user operating system and update the database
		$useros = getOS();
		$os = \App\Os::where('os',$useros)->count();
		if ($os > 0)
		{
			\App\Os::where('os',$useros)->increment('visits');
		} else {
			\App\Os::insert(['os' => $useros,'visits' => '1']);
		}
		// Get visitor browser
		$userbrowser = getBrowser();
		$browser = \App\Browser::where('browser',$userbrowser)->count();
		if ($browser > 0)
		{
			\App\Browser::where('browser',$userbrowser)->increment('visits');
		} else {
			\App\Browser::insert(['browser' => $userbrowser,'visits' => '1']);
		}
		// Get the website where the user came from
		$userreferrer = getReferrer();
		if(empty($userreferrer)){$userreferrer = 'Direct';}
		$referrer =  \App\Referrer::where('referrer',$userreferrer)->count();
		if ($referrer > 0)
		{
			\App\Referrer::where('referrer',$userreferrer)->increment('visits');
		} else {
			\App\Referrer::insert(['referrer' => $userreferrer,'visits' => '1']);
		}
		// Get visitors country
		$usercountry = getCountry();
		if($usercountry){
			\App\Country::where('iso',$usercountry)->increment('visitors');
		}
		// Visitors tracking tool
		if (isset(request()->tracking)){
			$trackingcode = request()->tracking;
			$tracking =  \App\Tracking::where('code',$trackingcode)->count();
			if ($tracking > 0)
			{
				\App\Tracking::where('code',$trackingcode)->increment('clicks');
			}
		}
		$data['stripe'] = \App\Payment::where('code','stripe')->first();
		$data['title'] = ($title) ? translate($title).' | '.translate($this->cfg->name) : translate($this->cfg->name);
		$data['desc'] = ($desc) ? $desc : $this->cfg->desc;
		$data['keywords'] = $this->cfg->key;
		$data['style'] = $this->style;
		$data['color'] = explode(',',$this->style->background);
		$data['cfg'] = $this->cfg;
		$data['tp'] = url("/themes/".$this->cfg->theme);
		$data['page'] = $page;
		$data['landing'] = $landing;
		$data['bg'] = $bg;
		$data['menu'] = $this->menu;
		$data['languages'] = $this->languages;
		$data['currencies'] = $this->currencies;
		return view('header')->with($data)->render(); 
	}
	public function footer()
	{
		// An array of social links to use them in the footer
		$social = array();
		if(!empty($this->cfg->facebook)){$social['facebook'] = $this->cfg->facebook;}
		if(!empty($this->cfg->instagram)){$social['instagram'] = $this->cfg->instagram;}
		if(!empty($this->cfg->youtube)){$social['youtube'] = $this->cfg->youtube;}
		if(!empty($this->cfg->twitter)){$social['twitter'] = $this->cfg->twitter;}
		if(!empty($this->cfg->tumblr)){$social['tumblr'] = $this->cfg->tumblr;}
		$links = $this->footer;
		return view('footer')->with(compact('social','links'))->render();
	}
	public function language($language_code)
	{
		// Check if the language exists and redirect the user
		if (\App\Language::where(['code' => escape($language_code)])->count() > 0)
		{
			setcookie('lang', $language_code,time()+31556926,'/');
		} else {
			return 'Language not found';
		}
		return redirect()->to('/');
	}
	public function currency($currency_code)
	{
		// Check if the currency exists and redirect the user
		if (\App\Currency::where(['code' => escape($currency_code)])->count() > 0)
		{
			setcookie('currency', $currency_code,time()+31556926,'/');
		} else {
			return 'currency not found';
		}
		return redirect()->to('/');
	}
	public function index()
	{
		$header = $this->header(false,false,false,true);
		$style = $this->style;
		if(preg_match("/(youtube.com)\/(watch)?(\?v=)?(\S+)?/", $this->style->media)){
			// Show video image from youtube
			parse_str(parse_url($this->style->media, PHP_URL_QUERY),$video);
			$media = '<a target="_blank" href="'.$this->style->media.'"><img class="landing-video" src="https://i3.ytimg.com/vi/'.$video['v'].'/mqdefault.jpg"></a>';
		} else {
			// Show image
			$media = '<img class="landing-image" src="'.$this->style->media.'">';
		}
		// Select the page builder blocs from database
		$blocs = \App\Bloc::where(['area' => 'home'])->orderby('o','asc')->get();
		$footer = $this->footer();
        $tp = url("/themes/".$this->cfg->theme);
		return view('index')->with(compact('header','style','media','blocs','footer','tp'));
	}
	public function cart()
	{
//		if ($this->cfg->floating_cart == 1) {
//			abort('404');
//		}
		$header = $this->header('Request For Quote',false,true);
		$footer = $this->footer();
		return view('cart')->with(compact('header','footer'));
	}
	public function wishlist()
	{
		$wishlist_json = request()->cookie('wishlist') != null ? json_decode(stripslashes(request()->cookie('wishlist')),true) : array();
		if (count($wishlist_json) > 0) {
			$ids = "";
			foreach($wishlist_json as $id){
				$ids = $ids . $id . ",";
			}
			$ids = rtrim($ids, ',');
			$products = \App\Product::whereRaw("id IN ({$ids})")->orderby('id','desc')->get();
		} else {
			$products = array();
		}
		$header = $this->header(translate('Wishlist'),false,true);
		$footer = $this->footer();
		return view('wishlist')->with(compact('header','products','footer'));
	}
	public function register()
	{
		if ($this->cfg->registration == 0 || session('customer') != '') {
			abort('404');
		}
		if(isset(request()->register)){
			if (!empty(request()->name) && !empty(request()->email) && !empty(request()->password)){
				$data['name'] = htmlspecialchars(request()->name);
				$data['email'] = htmlspecialchars(request()->email);
				$data['password'] = md5(request()->password);
				$data['sid'] = md5(microtime().uniqid());
				if(\App\Customer::where(['email' => $data['email']])->count() > 0) {
					$error = 'This email is already registerd !';
				} else {
					\App\Customer::insert($data);
					$error = false;
				}
			} else {
				$error = 'All fields are required !';
			}
			
		}
		$header = $this->header(translate('Registration'),false,true);
		$footer = $this->footer();
		return view('register')->with(compact('header','error','footer'));
	}
	public function login()
	{
		if ($this->cfg->registration == 0 || session('customer') != '') {
			abort('404');
		}
		if(isset(request()->login)){
			// Check email and password and redirect to the account
			$data['email'] = htmlspecialchars(request()->email);
			$data['password'] = md5(request()->password);
			if(empty($data['email']) or empty($data['password'])){
				$error = 'All fields are required';
			} else {
				if(\App\Customer::where($data)->count() > 0) {
					// Generate a new secure ID for this session and redirect to dashboard
					$secure_id = md5(microtime().uniqid());
					\App\Customer::where($data)->update(['sid' => $secure_id]);
					session(['customer' => $secure_id]);
					return redirect('account');
				} else {
					$error = 'Wrong email or password';
				}
			}
		}
		$header = $this->header(translate('Login'),false,true);
		$footer = $this->footer();
		return view('login')->with(compact('header','error','footer'))->render(); 
	}
	public function reset_password($token = false)
	{
		if (session('customer') != '') {
			abort('404');
		}
		if(isset(request()->send)){
			$data['email'] = request()->email;
			if(empty($data['email'])){
				$error = 'All fields are required';
			} else {
				if(\App\Customer::where($data)->count() > 0) {
					$customer = \App\Customer::where($data)->first();
					$key = md5(uniqid());
					\App\Password_reset::insert(['customer' => $customer->id,'key' => $key]);
					mailing('reset-password',array('link'=>url('reset-password/'.$key)),'Reset your password',$data['email']);
					$error = false;
				} else {
					$error = 'This account does not exist';
				}
			}
		}
		if($token){
			if(\App\Password_reset::where(['key' => $token])->count() == 0) {
				abort(404);
			}
			$id = \App\Password_reset::where(['key' => $token])->first()->customer;
			if(isset(request()->reset)){
				$pass = md5(request()->password);
				if(empty(request()->password)){
					$error = 'Please insert your new password';
				} else {
					$secure_id = md5(microtime());
					\App\Customer::where(['id' => $id])->update(['sid' => $secure_id,'password' => $pass]);
					\App\Password_reset::where(['customer' => $id])->delete();
					session(['customer' => $secure_id]);
					return redirect('account');
				}
			}
			$header = $this->header('Reset your password',false,true);
			$footer = $this->footer();
			return view('reset-password')->with(compact('header','error','footer'))->render();
		}
		$header = $this->header('Forgot password ?',false,true);
		$footer = $this->footer();
		return view('forgot-password')->with(compact('header','error','footer'))->render(); 
	}
	public function account()
	{
		$header = $this->header(translate('Account'),false,true);
		$orders = \App\Order::where('customer',customer('id'))->orderby('id','desc')->get();
		$footer = $this->footer();
		return view('account')->with(compact('header','orders','footer'))->render(); 
	}
	public function invoice($order_id)
	{
		// Check if the order exists and return order details
		if (\App\Order::where('customer',customer('id'))->where('id',$order_id)->orderby('id','desc')->count() == 0){
			abort(404);
		}
		$order = \App\Order::where('id',$order_id)->first();
		$header = $this->header(translate('Invoice'),false,true);
		$fields = \App\Field::orderby('id','asc')->get();
		$footer = $this->footer();
		return view('invoice')->with(compact('header','order','fields','footer'));
	}
	public function profile()
	{
		if(isset(request()->update)){
			if (!empty(request()->name) && !empty(request()->email)){
				$data['name'] = htmlspecialchars(request()->name);
				$data['email'] = htmlspecialchars(request()->email);
				if (!empty(request()->password)) {
					$data['password'] = md5(request()->password);
				} else {
					$data['password'] = customer('password');
				}
				\App\Customer::where('id',customer('id'))->update($data);
				$error = false;
			} else {
				$error = 'Name and email fields are required !';
			}
		}
		$header = $this->header(translate('Profile'),false,true);
		$footer = $this->footer();
		return view('profile')->with(compact('header','error','footer'));
	}
	public function logout()
	{
		// Clear the customer seesion 
		session(['customer' => '']);
		return redirect('login');
	}
	public function products($category = false)
	{
		$header = $this->header(translate('Products'),false,true);
		// Apply the product filters
		$price = array();
		$where = array();
		if (!empty(request()->min) && !empty(request()->max))
		{
			$where['price'] = "price BETWEEN '".escape(request()->min)."' AND '".escape(request()->max)."'";
		}
		if (!empty(request()->search))
		{
			$where['search'] = "(title LIKE '%".escape(request()->search)."%' OR text LIKE '%".escape(request()->search)."%')";
		}
		if ($category)
		{
			if (\App\Category::where('path',$category)->count() == 0){
				abort(404);
			}
			$category = \App\Category::where('path',$category)->first();
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
		$cats = $this->categories;
		$products = \App\Product::whereRaw($where)->orderby('id','desc')->get();
		$price['min'] = (count($products) > 0 ? \App\Product::whereRaw($where)->orderby('price','asc')->limit(1)->first()->price : 0);
		$price['max'] = (count($products) > 0 ? \App\Product::whereRaw($where)->orderby('price','desc')->limit(1)->first()->price : 0);
		$footer = $this->footer();
		return view('products')->with(compact('header','category','price','cats','products','footer'));
	}
	public function product($product_id)
	{
		// Check if product exists and return product details
		if (\App\Product::where('id',explode('-',$product_id)[0])->count() == 0){
			abort(404);
		}
		$product = \App\Product::where('id',explode('-',$product_id)[0])->first();
		$cat = \App\Category::where('id',$product->category)->first();
		$total_ratings = \App\Review::where('active',1)->where('product',$product->id)->count();
		$rating = 0;
		if ($total_ratings > 0){
			$rating_summ = \App\Review::where('active',1)->where('product',$product->id)->sum('rating');
			$rating = $rating_summ / $total_ratings;
		}
		$images = explode(',',$product->images);
		$title = $product->title;
		$desc = mb_substr($product->text,0,75);
		$header = $this->header(translate($title),$desc,true);
		// Select product reviews from the database
		$reviews = \App\Review::where('active',1)->where('product',$product->id)->orderby('time','desc')->get();
		$tp = url("/themes/".$this->cfg->theme);
		$footer = $this->footer();
		return view('product')->with(compact('header','product','cat','rating','total_ratings','images','reviews','tp','footer'));
	}
	public function blog()
	{
		$header = $this->header(translate('Blog'),false,true);
		$posts = \App\Post::orderby('time','desc')->get();
		$footer = $this->footer();
		return view('blog')->with(compact('header','posts','footer'));
	}
	public function post($post_id)
	{
		// Check if blog post exists and return post details
		if (\App\Post::where('id',explode('-',$post_id)[0])->count() == 0){
			abort(404);
		}
		$post = \App\Post::where('id',explode('-',$post_id)[0])->first();
		\App\Post::where('id',$post->id)->increment('visits');
		$title = $post->title;
		$desc = mb_substr($post->content,0,75);
		$header = $this->header(translate($post->title),$desc,false,true,url('/assets/blog/'.$post->images));
		// Get blocs of the page builder
		$blocs = \App\Bloc::where(['area' => 'post'])->orderby('o','asc')->get();
		$footer = $this->footer();
		return view('post')->with(compact('header','post','blocs','footer'));
	}
	public function page($page_id)
	{
		// Check if the page exists and return page title and content
		if (\App\Page::where('path',$page_id)->count() == 0){
			abort(404);
		}
		$page = \App\Page::where('path',$page_id)->first();
		$title = $page->title;
		$desc = mb_substr($page->content,0,75);
		$header = $this->header(translate($title),$desc,true);
		// Get blocs of the page builder
		$blocs = \App\Bloc::where(['area' => 'page'])->orderby('o','asc')->get();
		$footer = $this->footer();
		return view('page')->with(compact('header','page','blocs','footer'));
	}
	public function support()
	{
		if(isset(request()->send)){
			if (!empty(request()->name) && !empty(request()->email) && !empty(request()->subject) && !empty(request()->message)){
				// Escape the user entries and insert them to database
				$data['name'] = htmlspecialchars(request()->name);
				$data['title'] = htmlspecialchars(request()->subject);
				$data['email'] = htmlspecialchars(request()->email);
				$data['message'] = htmlspecialchars(request()->message);
				\App\Ticket::insert($data);
				$sent = true;
			} else {
				$sent = false;
			}
		}
		$header = $this->header(translate('Support'),false,false,true,url('/support/map'));
		$cfg = $this->cfg;
		$footer = $this->footer();
		return view('support')->with(compact('header','sent','cfg','footer'));
	}
	public function map()
	{
		// Return a static map from the Google maps api
		$map = 'http://maps.googleapis.com/maps/api/staticmap?zoom=12&format=png&maptype=roadmap&style=element:geometry|color:0xf5f5f5&style=element:labels.icon|visibility:off&style=element:labels.text.fill|color:0x616161&style=element:labels.text.stroke|color:0xf5f5f5&style=feature:administrative.land_parcel|element:labels.text.fill|color:0xbdbdbd&style=feature:poi|element:geometry|color:0xeeeeee&style=feature:poi|element:labels.text.fill|color:0x757575&style=feature:poi.business|visibility:off&style=feature:poi.park|element:geometry|color:0xe5e5e5&style=feature:poi.park|element:labels.text|visibility:off&style=feature:poi.park|element:labels.text.fill|color:0x9e9e9e&style=feature:road|element:geometry|color:0xffffff&style=feature:road.arterial|element:labels|visibility:off&style=feature:road.arterial|element:labels.text.fill|color:0x757575&style=feature:road.highway|element:geometry|color:0xdadada&style=feature:road.highway|element:labels|visibility:off&style=feature:road.highway|element:labels.text.fill|color:0x616161&style=feature:road.local|visibility:off&style=feature:road.local|element:labels.text.fill|color:0x9e9e9e&style=feature:transit.line|element:geometry|color:0xe5e5e5&style=feature:transit.station|element:geometry|color:0xeeeeee&style=feature:water|element:geometry|color:0xc9c9c9&style=feature:water|element:labels.text.fill|color:0x9e9e9e&size=640x250&scale=4&center='.urlencode(trim(preg_replace('/\s\s+/', ' ', $this->cfg->address)));
		$con = curl_init($map);
		curl_setopt($con, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($con, CURLOPT_HEADER, 0);
		curl_setopt($con, CURLOPT_RETURNTRANSFER, 1);
		return response(curl_exec($con))->header('Content-Type', 'image/png');
	}
	public function success()
	{
		$header = $this->header(translate('Success'),false,false,true);
		// remove cart products after the successfull payment
		setcookie('cart', '',time()+31536000,'/');
		setcookie('cart_options', '',time()+31536000,'/');
		$footer = $this->footer();
		return view('success')->with(compact('header','footer'));
	}
	public function failed()
	{
		$header = $this->header(translate('Failed'),false,false,true);
		$footer = $this->footer();
		return view('failed')->with(compact('header','footer'));
	}
}
