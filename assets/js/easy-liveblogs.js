(function ($) {

	$(document).ready(function () {

		$('.elb-liveblog-highlighted-post').addClass("elb-liveblog-highlight");

		setTimeout(function () {
			$('.elb-liveblog-highlighted-post').removeClass('elb-liveblog-highlight');
		}, 3000);

		var elb_document_title = $(document).find('title').text();
		var elb_new_posts = 0;
		var elb_load_new_btn = $('#elb-show-new-posts');
		var elb_load_more_btn = $('#elb-load-more');
		var elb_liveblog = $('.elb-liveblog-list');

		function elb_refresh_liveblog() {

			elb_exclude = [];

			$('.elb-liveblog-list li:not(.elb-liveblog-highlighted-post)').each(function (index, post) {
				elb_exclude.push($(post).data('elbPostId'));
			});

			$.ajax({
				url: elb.ajax_url,
				method: 'post',
				dataType: 'json',
				data: {
					action: 'elb_update_liveblog',
					exclude: elb_exclude,
					liveblog: elb.liveblog,
					after: elb_liveblog.find('li:first > .elb-liveblog-post-time > time').attr('datetime')
				},
				success: function (posts) {

					if (posts.length > 0) {

						$('.elb-no-liveblog-entries-message').remove();

						$.each(posts, function (index, post) {
							var post_html = $('<div>');

							post_html.html(post);
							post_html.find('li.elb-liveblog-post').hide();

							elb_liveblog.prepend(post_html.html());

							elb_new_posts++;
						});

						$(document).find('title').text('(' + elb_new_posts + ') ' + elb_document_title);

						var elb_update_message;

						if (elb_new_posts === 1) {
							elb_update_message = elb.new_post_msg.replace("%s", elb_new_posts);
						} else {
							elb_update_message = elb.new_posts_msg.replace("%s", elb_new_posts);
						}

						elb_load_new_btn.show().text(elb_update_message);

						typeof elb_after_update_liveblog_callback === 'function' && elb_after_update_liveblog_callback();

					} // end post length

				}
			});

			setTimeout(elb_refresh_liveblog, elb.interval * 1000);

		}

		if (elb.status !== 'closed') {
			elb_refresh_liveblog();
		}

		/////////

		elb_load_new_btn.click(function () { // reset
			$('.elb-liveblog-list li').not(':visible').fadeIn();
			elb_new_posts = 0;
			elb_load_new_btn.hide();
			$(document).find('title').text(elb_document_title);
		});

		/////////

		elb_load_more_btn.click(function () { // load more
			elb_load_more_btn.attr('disabled', 'disabled');

			$.ajax({
				url: elb.ajax_url,
				method: 'post',
				dataType: 'json',
				data: {
					action: 'elb_load_more',
					before: elb_liveblog.find('li:last > .elb-liveblog-post-time > time').attr('datetime')
				},
				success: function (posts) {
					elb_load_more_btn.removeAttr('disabled');

					if (posts.length > 0) {
						$.each(posts, function (index, post) {
							elb_liveblog.append(post);
						});
					} else {
						elb_load_more_btn.text(elb.now_more_posts).delay(2000).fadeOut(1000);
					}

					typeof elb_after_load_more_callback === 'function' && elb_after_load_more_callback();

				}
			});
		});

	});

})(jQuery);
