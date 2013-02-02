$(document).ready(function() {
	if (!Modernizr.input.list) {
		$("input[list]").each(function() {
			var input = $(this);
			var list = $("datalist#" + input.attr("list"));
			if (list) {
				var items = $.makeArray($("option", list).map(function(i, item) { return $(item).attr("value"); }));
				input.autocomplete({ source: items });
			}
		});
	}
});