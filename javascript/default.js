$(document).ready(function() {
	$("html").removeClass("no-js");

	enableUI();
	if (typeof EnableCommentPreviews !== 'undefined') {
		EnableCommentPreviews();
	}
});

function enableUI() {
	$(".js-ui-accordion").accordion({ header: ".js-ui-accordion-header", collapsible: true, active: false, heightStyle: 'content' });
}