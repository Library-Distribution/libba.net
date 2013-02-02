$(document).ready(function() {
	$("#review-list").accordion({ header: ".review-header", collapsible: true, active: false, heightStyle: 'content' });
	EnableCommentPreviews();
});