<?php	$Auth->checkLogin(); ?>	
<!doctype html>
<html>
<head>
	<link href="https://fonts.googleapis.com/css?family=Play" rel="stylesheet">
	<link href="<?php echo $this->siteurl; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
	<header>
		<div id="loginbar">
			<div class="left">
				Turn: {{App|GetOption|turn}}
			</div>
			<div class="right fontsmall">
				<?php if ($Auth->logged_user_id > 0) { ?>
					Hello, <?php echo $Auth->data->email; ?>
				<?php } else { ?>
					You are not logged in.
				<?php } ?>
			</div>
		</div>
		<div class="breadcrumb">{{Breadcrumb|Render}}</div>
		<!--
		<div class="menu">
			<ul>
				<li><a href="{{Link|Get|/}}">Home</a></li>
				<li><a href="{{Link|Get|/chi-siamo/}}">Chi siamo</a></li>
				<li><a href="{{Link|Get|/registrati/}}">Registrati</a></li>
				<li><a href="{{Link|Get|/login/}}">Login</a></li>
				<li><a href="{{Link|Get|/cron/passturn/}}">Pass turn</a></li>
				<li><a href="{{Link|Get|/init/}}">Init database</a></li>
			</ul>
		</div>
		-->
	</header>