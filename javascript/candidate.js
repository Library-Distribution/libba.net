$(document).ready(function() {
	$("#candidate-list").accordion({ header: ".candidate-header", collapsible: true, active: false, heightStyle: 'content' });
	EnableCommentPreviews();
});