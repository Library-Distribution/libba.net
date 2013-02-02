$(document).ready(function() {
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