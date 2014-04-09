<? // FYI, this patial is populated from a view composer ?>

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			
			<?// This is the button to expand on mobile ?>
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			
			<a class="brand" href="<?=route('decoy')?>"><?=Config::get('decoy::site_name')?></a>
			<div class="nav-collapse collapse">
				
				<?// Login state ?>
				<? if (App::make('decoy.auth')->check()): ?>
					
					<?// The menu ?>
					<ul class="nav">
						<? foreach($pages as $page): ?>
							
							<?// Dropdown menu?>
							<? if (!empty($page->children)):

								// Buffer the output so that it is only shown if children were added.  There
								// could be none if they were hidden by permissions rules
								ob_start();
								$child_added = false;

								?>
								<li class="dropdown <?=$page->active?'active':null?>">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?=$page->label?> <b class="caret"></b></a>
									<ul class="dropdown-menu">
										
										<?// Loop through children ?>
										<? foreach($page->children as $child): ?>
											<? if (!empty($child->divider)): ?>
												<li class="divider"></li>
											<? elseif(app('decoy.auth')->can('read', $child->url)): 
												$child_added = true; ?>
												<li class="<?=$child->active?'active':null?>"><a href="<?=$child->url?>"><?=$child->label?></a></li>
											<? endif ?>
										<? endforeach ?>
										
									</ul>
								</li>
								<?
								// Only show the dropdown if a child was added
								if ($child_added) ob_end_flush();
								else ob_end_clean();
								?>
								
							<?// Standard link ?>
							<? elseif(app('decoy.auth')->can('read', $page->url)): ?>
								<li class="<?=$page->active?'active':null?>"><a href="<?=$page->url?>"><?=$page->label?></a></li>
							<? endif ?>
						<? endforeach ?>
					</ul>
					
					<? $auth = App::make('decoy.auth'); ?>
					<ul class="nav pull-right">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<span>Hi, <?=$auth->userName()?>!</span>
								<img src="<?=$auth->userPhoto()?>" class="gravatar"/>
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								
								<? if (is_a($auth, 'Bkwld\Decoy\Auth\Sentry')): ?>
									<li><a href="<?=DecoyURL::action('Bkwld\Decoy\Controllers\Admins@index')?>">Admins</a></li>
									<li><a href="<?=$auth->userUrl()?>">Your account</a></li>
									<li class="divider"></li>
								<? endif ?>
								
								<? $divider = false; ?>
								<? if ($auth->developer()): $divider = true; ?>
									<li><a href="<?=route('decoy\commands')?>">Commands</a></li>
								<? endif ?>
								
								<? if (count(Bkwld\Decoy\Models\Worker::all())): $divider = true; ?>
									<li><a href="<?=route('decoy\workers')?>">Workers</a></li>
								<? endif ?>
								
								<? if ($divider): ?>
									<li class="divider"></li>
								<? endif ?>
																
								<li><a href="/">Public site</a></li>
								<li><a href="<?=$auth->logoutUrl()?>">Log out</a></li>
							</ul>
						</ul>
					
				<? endif ?>
			</div>
		</div>
	</div>
	
	<?// The progress indicator for ajax requests?>
	<?= View::make('decoy::layouts._ajax_progress') ?>
</div>
