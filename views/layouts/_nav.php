<? // FYI, this patial is populated from a view composer ?>


<div class="user-bar">
	<a class="mobile-menu" href="#">
<!-- 		<div class="label">Menu</div>
 -->		<div class="icon"></div>
	</a>
	<div class="user-info">
		<div class="notify"><a href="#"></a></div>
<!-- 		<div class="user"><img src="/img/tmp/user-small.jpg" alt="">
			<div class="wrap"><div class="inner">Mister Username</div></div>
		</div>
 -->
		<ul class="nav pull-right user">
			<li class="dropdown">
				<a class="user-dropdown" href="#" class="dropdown-toggle" data-toggle="dropdown">
					<span>Hi, <?=Decoy_Auth::user_name()?>!</span>
					<img src="<?=Decoy_Auth::user_photo()?>" class="gravatar"/>
					<b class="caret"></b>
				</a>
				<ul class="dropdown-menu">
					<? if (is_a(new Decoy_Auth, 'Decoy\Auth')): ?>
						<li><a href="<?=action('decoy::admins')?>">Admins</a></li>
						<li class="divider"></li>
					<? endif ?>
					<? if (Decoy_Auth::developer()): ?>
						<li><a href="<?=action('decoy::tasks')?>">Tasks</a></li>
						<li class="divider"></li>
					<? endif ?>
					<li><a href="<?=Decoy_Auth::user_url()?>">Account</a></li>
					<li><a href="<?=Decoy_Auth::logout_url()?>">Log out</a></li>
				</ul>
			</li>
		</ul>


	</div>
	<div class="edit-nav">
<!-- 		<div class="col"><a class="dashboard" href="#">Dashboard</a></div>
 -->		<div class="col"><a class="edit-profile" href="#">Edit Profile</a></div>
	</div>
</div>

<div id="admin-navbar" class="navbar navbar-inverse " data-js-view="navbar">
	<div class="navbar-inner">

		<div class="container">

			<?// This is the button to expand on mobile ?>
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			
			<a class="brand" href="<?=action('decoy::')?>"><?=Config::get('decoy::decoy.site_name')?></a>
			<div class="nav-collapse collapse">
				
				<?// Login state ?>
				<? if (Decoy_Auth::check()): ?>
					
					<?// The menu ?>
					<ul class="nav admin-nav">
						<? foreach($pages as $page): ?>
							
							<?// Dropdown menu?>
							<? if (!empty($page->children)): ?>
								<li class="dropdown <?=$page->active?'active':null?>">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?=$page->label?> <b class="caret"></b></a>
									<ul class="dropdown-menu" id="nested">
										
										<?// Loop through children ?>
										<? foreach($page->children as $child): ?>
											<? if (!empty($child->divider)): ?>
												<li class="divider"></li>
											<? else: ?>
												<li class="<?=$child->active?'active':null?>"><a href="<?=$child->url?>"><?=$child->label?></a></li>
											<? endif ?>
										<? endforeach ?>
										
									</ul>
								</li>
								
							<?// Standard link ?>
							<? else: ?>
								<li class="<?=$page->active?'active':null?>"><a href="<?=$page->url?>"><?=$page->label?></a></li>
							<? endif ?>
						<? endforeach ?>
					</ul>
					
					
					
				<? endif ?>
			</div>
		</div>
	</div>
	
	<?// The progress indicator for ajax requests?>
	<?= render('decoy::layouts._ajax_progress') ?>
</div>