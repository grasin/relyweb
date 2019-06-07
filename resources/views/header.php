<!DOCTYPE html>
<html>
    <head>
		<meta charset='utf-8'/>
		<title><?=$title ?></title>        
		<meta name="viewport" content="initial-scale = 1.0,maximum-scale = 1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
		<meta content='<?=htmlspecialchars($desc) ?>' name='description'/>
		<meta content='<?=$keywords ?>' name='keywords'/>
		<base href="<?=url('') ?>/" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
		<link href="https://fonts.googleapis.com/css?family=Varela+Round" rel="stylesheet"> 
		<link rel="stylesheet" href="<?=$tp ?>/assets/style.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
		<script src="<?=$tp ?>/assets/plugins.js"></script>
		<?php if ($stripe->active == 1) {?><script type="text/javascript" src="https://js.stripe.com/v2/" async></script><?php }?>

		<script>
			var sitename = '<?=translate($cfg->name) ?>';
			var empty_cart = '<?=translate('Your cart is empty') ?>';
			var request_quote = '<?=translate('Request Quote') ?>';
			var no_products = '<?=translate('No products added for RFQ') ?>';
			var checkout = '<?=translate('Checkout') ?>';
			var continue_to_payment = '<?=translate('Continue') ?>';	
			var get_prices = '<?=translate('Get Quote') ?>';			
			var add_to_wishlist = '<?=translate('Add to wishlist')?>';			
			var remove_from_wishlist = '<?=translate('Remove from wishlist')?>';			
			var success = '<?=translate('Success')?>';			
			var updated = '<?=translate('Updated')?>';			
			var stock_unavailable = '<?=translate('Stock unavailable')?>';			
			var failed = '<?=translate('Failed')?>';			
			<?php if ($stripe->active == 1) {?>var stripe_key = '<?=json_decode($stripe->options,true)['key']?>';<?php }?>
			<?=$style->js ?>

		</script>
		<script src="<?=$tp ?>/assets/main.js"></script>
	</head>
	<body dir="ltr" <?php if ($page == true) {?>class="page"<?php }?>>
		<div class="search-modal">
			<div class="col-md-5">
				<input id="search-input" placeholder="<?=translate('Search for products') ?>"/>
				<button class="search-toggle">x</button>
				<div id="search-results">
				</div>
			</div>
		</div>
		<button class="toggle-cart"><img style='height:80px; ' src='assets/rfq.png'></button>
		<div id="cart">
			<button class="toggle-cart"><i class="icon-close"></i></button>
			<div id="cart-header">
				Request for Quote
				<button class="pull-right" onclick="$('#cart').toggle('300');"><i class="icon-close"></i></button>
			</div>
			<div id="cart-content">
				<div class="loading"></div>
			</div>
		</div>
		<?php if ($landing == true) {?><div id="wrap"><div class="page-warpper cover-header cover"><?php } else {?><div id="wrap"><div class="page"><?php }?>
		<style>
			<?php if ($bg != false) {?>
			.cover {background:linear-gradient(to bottom, rgba(0, 0, 0, 0.5),rgba(255, 255, 255, 0)),url("<?=$bg ?>") no-repeat 0%/cover scroll;}
			<?php } else {?>
			.cover {background:linear-gradient(to right, <?=$style->background ?>) repeat scroll 0% 0%;}
			<?php }?>
			.bg, #navbar.collapse.in, #navbar.collapsing {
			background:linear-gradient(to right, <?=$style->background ?>) repeat scroll 0% 0%;
			}
			.c {
			color: <?=$color[0] ?>;
			}
			<?=$style->css ?>
		</style>
		<div class="header <?php if ($page != false) {?>bg<?php }?>">
			<nav class="navbar navbar-default">
				<div class="container ">
					<div class="navbar-header">
						<a class="search-toggle mobile-search">
							<i class="icon-magnifier"></i>
						</a>
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand smooth" rel="home" href="<?=url('')?>"><img src="<?=$cfg->logo ?>"></a>
					</div>
					
					<div class="collapse navbar-collapse" id="navbar">
						
						<ul class="nav navbar-nav">
							<?php foreach ($menu as $menu){?>
								<?php
									$childs = \App\Menu::where(['parent' => $menu->id])->orderby('o','desc')->get();
									if (count($childs) == 0){
								?>
									<li>
										<a href="<?=$menu->link ?>" class="smooth"><?=translate($menu->title) ?></a>
									</li>
								<?php } else { ?>
									<li class="dropdown">
										<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false" href="<?=$menu->link ?>" class="smooth"><?=translate($menu->title) ?></a>
										<ul class="dropdown-menu">
											<?php foreach ($childs as $child) {?>
												<li><a class="smooth" href="<?=$child->link?>"><?=translate($child->title) ?></a></li>
											<?php } ?>
										</ul>
									</li>
								<?php }?>
							<?php }?>
						</ul>
						<ul class="nav navbar-nav navbar-right">
							<li class='icons-menu'>
								<a href="<?=url('request_for_quote')?>" class="smooth" data-title="Request for Quote">
									<i class="far fa-file-alt"></i>&nbsp;&nbsp;Orders <b class="hidden-md hidden-sm hidden-lg"> RFQ List </b>
								</a>
							</li>
                            <li class='icons-menu'>
								<a href="<?=url('request_for_quote')?>" class="smooth" data-title="Request for Quote">
									<i class="far fa-money-bill-alt"></i>&nbsp;&nbsp;RFQ List <b class="hidden-md hidden-sm hidden-lg"> RFQ List </b>
								</a>
							</li>
							<?php if ($cfg->registration == 1) {?>
							<li class="dropdown icons-menu">
								<a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
									<i class="icon-user" style="margin: 0px;"></i><b class="hidden-md hidden-sm hidden-lg"> <?=translate('Account') ?></b>
								</a>
								<ul class="dropdown-menu">
									<?php if (empty(session('customer'))) {?>
									<li><a class="smooth" href="<?=url('register')?>"><?=translate('Register') ?></a></li>
									<li><a class="smooth" href="<?=url('login')?>"><?=translate('Login') ?></a></li>
									<?php } else {?>
									<li><a class="smooth" href="<?=url('account')?>"><?=translate('Account') ?></a></li>
									<li><a class="smooth" href="<?=url('profile')?>"><?=translate('Profile') ?></a></li>
									<li><a href="<?=url('logout')?>"><?=translate('Logout') ?></a></li>
									<?php }?>
								</ul>
							</li>
							<?php }?>
							<li class='icons-menu'>
								<a class="search-toggle">
									<i class="icon-magnifier" style="margin: 0px;"></i>
								</a>
							</li>
							<li class="dropdown hidden-md hidden-sm hidden-lg">
								<a data-toggle="collapse" data-target="#navbar">
									<i class="icon-close" style="margin: 0px;"></i><b> Close</b>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</nav>
		</div>	
        
    