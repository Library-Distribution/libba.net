$(document).ready(function() {
	$("#items-list").accordion({ heightStyle: "content", header: "h3", collapsible: true, active: false }); // apply jQuery-UI styles to item list
	$("#items-list .letter-container").removeClass("letter-container"); // supress styles for javascript disabled
});