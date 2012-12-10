<? // FYI, this patial is populated from a view composer ?>

<div class="navbar navbar-inverse navbar-fixed-top" data-js-view="navbar">
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
				<? if (Sentry::check() && Sentry::user()->in_group('admins')): ?>
					
					<?// The menu ?>
					<ul class="nav">
						<? foreach($pages as $page): ?>
							
							<?// Dropdown menu?>
							<? if (!empty($page->children)): ?>
								<li class="dropdown <?=$page->active?'active':null?>">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?=$page->label?> <b class="caret"></b></a>
									<ul class="dropdown-menu">
										
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
					
					<ul class="nav pull-right">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<span>Hi, 
									<?=Sentry::user()->get('metadata.first_name')?>!
								</span>
								<img src="<?=HTML::gravatar(Sentry::user()->get('email'))?>" class="gravatar"/>
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li><a href="<?=action('decoy::admins')?>">Admins</a></li>
								<li class="divider"></li>
								<li><a href="<?=action('admin.account')?>">Account</a></li>
								<li><a href="<?=action('admin.account@logout')?>">Log out</a></li>
							</ul>
						</ul>
					
				<? endif ?>
			</div>
		</div>
	</div>
	
	<?// The progress indicator for ajax requests?>
	<?= render('decoy::layouts._ajax_progress') ?>
</div>