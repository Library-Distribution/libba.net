function loadUserContent(event)
{
	var new_link = $(event.currentTarget);
	var curr_link = $(".nav-current");

	if (new_link != curr_link) {
		curr_link.toggleClass("nav-current");
		new_link.toggleClass("nav-current");

		$("div#content-" + curr_link.attr("title")).css("display", "none");
		$("div#content-" + new_link.attr("title")).css("display", "block");

		event.preventDefault();
	}
}

function getPresentTags(selector, attribute)
{
	var list = [];
	$(selector).each(function() {
		var val = $(this).attr(attribute);
		if ($.inArray(val, list) == -1) {
			list.push(val);
		}
	});
	return list;
}

$(document).ready(function() {
	$("div#user-navigation a.nav-current").click(loadUserContent);

	var dummy_div = $("<div id='dummy-div' style='display:none'/>");
	$("body").append(dummy_div);

	// compile loaded stylesheets
	var loaded_styles = getPresentTags("html > head > link[rel='stylesheet']", "href");

	// compile loaded javascript
	var loaded_scripts = getPresentTags("html > head > script", "src");

	// loop through all profile sub-pages
	$("div#user-navigation a").each(function() {
		var link = $(this);
		if (link.css("display") != "none" && !link.hasClass("nav-current"))
		{
			var title = $(this).attr("title");

			// load page content
			var div = $("<div id='content-" + title + "' style='display: none'></div>");
			$("#page-content").append(div);
			$(div).load("./" + title + " #page-content #content-" + title + " *", function(response) {
				link.click(loadUserContent);
			});

			// load missing stylesheets
			$(dummy_div).load("./" + title + " link[rel='stylesheet']", function() {
				$("link", $(dummy_div)).each(function() {
					var href = $(this).attr("href");
					if ($.inArray(href, loaded_styles) == -1) {
						var link = $("<link rel='stylesheet' type='text/css' href='" + href + "'/>");
						$("html > head").append(link);
					}
				});
			});

			// load missing javascript
			$(dummy_div).load("./" + title + " script", function() {
				$("script", $(dummy_div)).each(function() {
					var src = $(this).attr("src");
					if ($.inArray(src, loaded_scripts) == -1) {
						$.getScript(src);
					}
				});
			});
		}
	});
});

