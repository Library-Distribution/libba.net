$(document).ready(function() {
	Modernizr.load([{
			test: Modernizr.input.list,
			nope: '/javascript/polyfills/datalistAutocomplete.js'
		},
		{
			test: Modernizr.input.required,
			nope: '/javascript/polyfills/checkRequiredFields.js'
		}]);
});