$(document).ready(function() {
	if (!Modernizr.input.list) {
		var users = $.makeArray($("datalist#registered-users option").map(function(i, item) { return $(item).attr("value"); }));
		console.log(users);
		$("input#user-name").autocomplete({ source: users });
	}
});