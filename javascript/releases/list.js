$(document).ready(function() {
	$("#release-list").accordion({ header: ".release-header", collapsible: true, active: false, heightStyle: 'content' });
	EnableCommentPreviews();
});