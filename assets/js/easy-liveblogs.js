(function ($) {

	$(document).ready(function () {

		if ($('.elb-liveblog').length === 0) {
			return false;
		}

		var elb_first_load = true;
		var elb_document_title = $(document).find('title').text();
		var elb_new_posts = 0;
		var elb_load_new_btn = $('#elb-show-new-posts');
		var elb_load_more_btn = $('#elb-load-more');
		var elb_liveblog = $('.elb-liveblog');
		var elb_liveblog_loader = $('.elb-loader');
		var elb_liveblog_list = $('.elb-liveblog-list');
		var elb_status_message = $('.elb-liveblog-closed-message');
		var elb_liveblog_endpoint = elb_liveblog.data('endpoint');
		var elb_show_entries = elb_liveblog.data('showEntries');
		var elb_append_timestamp = elb_liveblog.data('appendTimestamp');
		var elb_highlighted_entry = elb_liveblog.data('highlightedEntry');

		function elb_get_time() {
			var d = new Date();
			var time = d.getTime();

			if (time === 0) {
				return 0;
			}
			return Math.round(time / 60000);
		}

		function elb_refresh_liveblog() {
			var elb_liveblog_endpoint_url = elb_liveblog_endpoint;

			if (elb_append_timestamp) {
				elb_liveblog_endpoint_url = elb_liveblog_endpoint + '?_=' + elb_get_time();
			}

			$.ajax({
				url: elb_liveblog_endpoint_url,
				method: 'get',
				dataType: 'json',
				success: function (feed) {
					elb_liveblog_list.html();
					elb_liveblog_loader.hide();

					$.each(feed.updates, function (index, post) {
						var go_to = false;
						var new_post = $('<div>' + post.html + '</div>');
						var current_post = elb_liveblog_list.find('li[data-elb-post-id="' + post.id + '"]');

						if (elb_first_load) {
							if ((index + 1) > elb_show_entries) {
								new_post.find('> li').addClass('elb-hide');
								new_post.find('> li').addClass('elb-liveblog-initial-post');
								elb_load_more_btn.show();
							}

							if (post.id === elb_highlighted_entry) {
								go_to = true;

								new_post.find('> li').addClass('elb-liveblog-highlight');
								new_post.find('> li').removeClass('elb-hide');
							}

							elb_liveblog_list.append(new_post.html());

							if (go_to) {
								$(document).scrollTop(elb_liveblog_list.find('> li[data-elb-post-id="' + post.id + '"]').offset().top);
							}

							return;
						}

						if (!elb_first_load && current_post.length != 0) {
							current_post.find('time').replaceWith(new_post.find('time'));
							current_post.find('.elb-liveblog-post-heading').replaceWith(new_post.find('.elb-liveblog-post-heading'));

							// Update content only if it doens't contain iframe's due to possible layout shifts.
							if (current_post.find('.elb-liveblog-post-content iframe').length == 0) {
								current_post.find('.elb-liveblog-post-content').replaceWith(new_post.find('.elb-liveblog-post-content'));
							}

							return;
						}

						if (!elb_first_load && current_post.length == 0) {
							new_post.find('li').addClass('elb-new');

							new_post.find('li').hide();

							elb_new_posts++;

							elb_liveblog_list.prepend(new_post.html());

							return;
						}
					});

					typeof elb_after_feed_load === 'function' && elb_after_feed_load(feed);

					elb_first_load = false;

					if (elb_liveblog_list.find('> li').length == 0) {
						$('.elb-no-liveblog-entries-message').show();
					}

					if (elb_new_posts > 0) {
						$(document).find('title').text('(' + elb_new_posts + ') ' + elb_document_title);

						var elb_update_message;

						if (elb_new_posts === 1) {
							elb_update_message = elb.new_post_msg.replace("%s", elb_new_posts);
						} else {
							elb_update_message = elb.new_posts_msg.replace("%s", elb_new_posts);
						}

						elb_load_new_btn.show().text(elb_update_message);

						typeof elb_after_update_liveblog_callback === 'function' && elb_after_update_liveblog_callback();
					}

					if (feed.status === 'closed') {
						elb_status_message.show();
					}
				}
			});

			setTimeout(elb_refresh_liveblog, elb.interval * 1000);
		}

		elb_refresh_liveblog();

		/////////

		elb_load_new_btn.click(function () { // reset
			$('.elb-liveblog-list > li.elb-new').not(':visible').fadeIn();
			elb_new_posts = 0;
			elb_load_new_btn.hide();
			$(document).find('title').text(elb_document_title);
		});

		/////////

		elb_load_more_btn.click(function () { // load more
			$('.elb-liveblog-list li.elb-hide.elb-liveblog-initial-post').each(function (index, post) {
				if (elb_show_entries > index) {
					$(this).removeClass('elb-hide');
				}
			});

			if ($('.elb-liveblog-list li.elb-hide.elb-liveblog-initial-post').length == 0) {
				elb_load_more_btn.text(elb.now_more_posts).delay(2000).fadeOut(1000);
			}

			typeof elb_after_load_more_callback === 'function' && elb_after_load_more_callback();
		});

	});

})(jQuery);
