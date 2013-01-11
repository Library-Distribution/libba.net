$(document).ready(function() {
	$.getJSON("api/users/list", function(data) {
		$("input#user-name").autocomplete({ source: $.map(data, function(user) { return user['name']; }) });
	});
});