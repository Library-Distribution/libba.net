$(document).ready(function() {
	$("html").removeClass("no-js");

	enableUI();
	enableCommentPreviews();
});

function enableUI() {
	$(".js-ui-accordion").accordion({ header: ".js-ui-accordion-header", collapsible: true, active: false, heightStyle: 'content' });
}

function enableCommentPreviews() {
	// add and enable the preview block
	$(".preview-source").after("<div class='preview-block'><h3>Comment preview:</h3><div class='markdown'><span class='preview-empty'>Nothing to preview yet. Type in the box above and see the preview here...</span></div></div>")
	.change(_load_comment_preview_).keyup(_load_comment_preview_);

	// add preview options
	$(".preview-source").before("preview: <label style='display: inline-block'><input type='radio' name='previewMode'/>permanently</label>"
							+ "<label style='display:inline-block'><input type='radio' name='previewMode' checked='checked'/>while editing</label>"
							+ "<label style='display:inline-block'><input type='radio' name='previewMode'/>never</label>");

	// enable preview options
	$("form").has(".preview-source").change(function() { // attach event handler to forms with preview sources
		$(".preview-source + .preview-block", this).css("display", this.previewMode[0].checked ? "block" : (this.previewMode[1].checked ? "" : "none"));
	});
}

function _load_comment_preview_() { // internal use only!
	var output = $(this).next("div.preview-block").find("div.markdown");
	$.post("/internal/transform_comments.php", { "text" : $(this).val() }, function(data, status) {
		if (status == "success") {
			output.html($.trim(data) ? data : "<span class='preview-empty'>Nothing to preview yet. Type in the box above and see the preview here...</span>");
		}
	});
}