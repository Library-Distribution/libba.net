$(document).ready(function() {
	if (!Modernizr.input.list) {
		var users = $.makeArray($("datalist#registered-users option").map(function(i, item) { return $(item).attr("value"); }));
		$("input#user-name").autocomplete({ source: users });
	}

	if (!Modernizr.input.required) { // the browser doesn't check if required fields are filled in
		if (document.up) { // the form (named "up") exists
			document.up.submit_btn.disabled = true; // disable submit button initially
			$("form input[type!='hidden']").change(function() {
				document.up.submit_btn.enabled = (document.up.package.value != "" && document.up.user.value != "" && document.up.password.value != ""); // only enable submit if everything filled in
			});
		}
	}
});