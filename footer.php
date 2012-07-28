<div id="footer">
	<a href="index"><img src="logo.png" alt="ALD logo" class="logo"/></a>
	<table>
		<tr>
			<td>
				<ul>
					<li><a href="index">Start page</a></li>
					<li><a href="help">Help</a></li>
					<li><a href="about">About</a></li>
				</ul>
			</td>
			<td>
				<ul>
					<?php $redirect_url = urlencode($_SERVER["REQUEST_URI"]); ?>
					<li><a href="viewuser">Users</a></li>
					<li>
					<?php if (isset($_SESSION) && !empty($_SESSION["user"])) {	?>
						<a href="login?mode=logout&amp;redirect=<?php echo $redirect_url; ?>">Logout</a>
					<?php } else { ?>
						<a href="login?mode=login&amp;redirect=<?php echo $redirect_url; ?>">Login</a> or <a href="login.php?mode=register&amp;redirect=<?php echo $redirect_url; ?>">Register</a>
					<?php } ?>
					</li>
					<li><a href="upload">Upload a new app or lib</a></li>
				</ul>
			</td>
			<td>
				<ul>
					<li><a href="http://www.autohotkey.proboards.com">Discussion forum</a></li>
					<li><a href="http://www.autohotkey.com/comunity">AutoHotkey forum</a></li>
					<li><a href="http://de.autohotkey.com/forum">AutoHotkey forum (DE)</a></li>
				</ul>
			</td>
		</tr>
	</table>
</div>