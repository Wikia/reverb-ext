(function(){
	var selector = "input[name='wpreverb-user-account[]'],input[name='wpreverb-user-interest[]'], input[name='wpreverb-article-edit[]']";
	if ($("input[name='wpreverb-email-frequency']:checked").val() == 0) {
		$(selector).each(function(){
			var name = $(this).attr('id');
			if (name.indexOf('email') !== -1) {
				$(this).attr("disabled", true);
			}
		});
	}
	$("input[name='wpreverb-email-frequency']").change(function(){
		if ($(this).val() == "1") {
			$(selector).each(function(){
				$(this).removeAttr("disabled");
			});
		} else {
			$(selector).each(function(){
				var name = $(this).attr('id');
				if (name.indexOf('email') !== -1) {
					$(this).attr("disabled", true);
				}
			});
		}
	});
})();