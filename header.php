<div id="navigation">
	<a href="index" title="home page">
		<div style="top:3%" id="nav-home">
			<img alt="home" src="images/home.png" style="top:05%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">Go back to the main page</div>
			</div>
		</div>
	</a>
	<a href="items" title="libraries and applications">
		<div style="top:16%">
			<img alt="items" src="images/items.png" style="top:18%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">Browse the available libraries and applications</div>
			</div>
		</div>
	</a>
	<a href="users" title="registered users">
		<div style="top:29%">
			<img alt="users" src="images/users.png" style="top:31%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">Find registered users and moderators</div>
			</div>
		</div>
	</a>
	<a href="reviews" title="code review">
		<div style="top:42%">
			<img alt="help" src="images/review.png" style="top:44%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">See how new items are checked for bad and malicious code</div>
			</div>
		</div>
	</a>
	<a href="candidates" title="stdlib candidates">
		<div style="top:55%">
			<img alt="help" src="images/achievements/stdlib.png" style="top:57%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">Here the new libraries for the standard library are selected</div>
			</div>
		</div>
	</a>
	<a href="upload" title="package upload">
		<div style="top:68%">
			<img alt="upload" src="images/activity/upload.png" style="top:70%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">Upload and share your own library or app</div>
			</div>
		</div>
	</a>
	<a href="help" title="help index">
		<div style="top:81%">
			<img alt="help" src="images/help.png" style="top:83%;"/>
			<div class="nav-triangle" style="top:27.5%;">
				<div class="nav-tooltip">Need help with anything? This link is for you!</div>
			</div>
		</div>
	</a>
</div>

<div id="login">
	<?php
		if (isset($_SESSION["user"]))
		{
			echo "Welcome<br/><a href='users/$_SESSION[user]/profile'>$_SESSION[user]</a>!<hr/><a href='logout'>Logout</a>";
		}
		else
		{
			echo 'Welcome!<hr/><a href="login">Login</a><hr/><a href="register">Register</a>';
		}
	?>
</div>