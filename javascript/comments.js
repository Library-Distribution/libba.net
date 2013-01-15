function _load_comment_preview_() {
	var output = $(this).next("div.preview-block").find("div.markdown");
	$.post("/transform_comments.php", { "text" : $(this).val() }, function(data, status) {
		if (status == "success") {
			output.html($.trim(data) ? data : "<span class='preview-empty'>Nothing to preview yet. Type in the box above and see the preview here...</span>");
		}
	});
}

function EnableCommentPreviews() {
	$(".preview-source").after($("<div class='preview-block'><h3>Comment preview:</h3><div class='markdown'><span class='preview-empty'>Nothing to preview yet. Type in the box above and see the preview here...</span></div></div>"));
	$(".preview-source").change(_load_comment_preview_).keyup(_load_comment_preview_);
}