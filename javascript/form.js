$(document).ready(function() {

	// polyfill for autocomplete lists (using jQuery UI)
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

	// polyfill for fields marked as 'required' (disable submit buttons)
	if (!Modernizr.input.required) { // the browser doesn't check if required fields are filled in
		$("form").each(function() { // handle each form separately
			var form = $(this);
			$("input[type='submit']", form).prop('disabled', true); // disable the submit button initially
			$("input[type!='hidden'][required]", form).change(function() { // if any real required input field changes
				var fulfilled = true;
				$("input[type!='hidden'][required]", form).each(function() { // go through all the (required) input fields
					fulfilled = !!$(this).val() && fulfilled;
				});
				$("input[type='submit']", form).prop('disabled', !fulfilled) // if all input fields have some value, enable submit button
			});
		});
	}

});