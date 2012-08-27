<?php

$template = "Hi {%NAME%},

Welcome to the AutoHotkey Library Distribution system ALD.
You have requested registration with the following data:

	Name: {%NAME%}
	Mail: {%MAIL%}
	(Password not included for security)

To complete your registration, go to <a href=\"" . ROOT_URL . "register/{%TOKEN%}\">" . ROOT_URL . "register/{%TOKEN%}</a> and follow the steps described there.
If you do not follow this step, your registration will expire after some time. You can always start a new registration.

If you have not requested registration, you may safely ignore this mail.";

?>