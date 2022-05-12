jQuery(function ($) {
	var elb_liveblog = {
		liveblog: null,
		document_title: null,
		first_load: true,
		new_posts: 0,
		timestamp: false,
		loader: null,
		show_new_button: null,
		load_more_button: null,
		list: null,
		status_message: null,

		init: function() {
			this.document_title = $(document).find('title').text();
			this.liveblog = '.elb-liveblog';
			this.show_new_button = '#elb-show-new-posts';
			this.load_more_button = '#elb-load-more';
			this.loader = '.elb-loader';
			this.list = '.elb-liveblog-list';
			this.status_message = '.elb-liveblog-closed-message';
			
			this.fetch();
			
			this.getElement('show_new_button').click(() => {
				this.showNew();
			});
			
			this.getElement('load_more_button').click(() => {
				this.loadMore();
			});
		},

		getLiveblog: function() {
			return $(this.liveblog);
		},

		getElement: function(name) {
			return this.getLiveblog().find(this[name]);
		},
		
		fetch: function() {
			$.ajax({
				url: this.getEndpoint(),
				method: 'get',
				dataType: 'json',
				success: (feed) => {
					this.getElement('list').html();
					this.getElement('loader').hide();

					$.each(feed.updates, (index, post) => {
						var go_to = false;
						var new_post = $('<div>' + post.html + '</div>');
						var current_post = this.getElement('list').find('li[data-elb-post-id="' + post.id + '"]');

						if (this.first_load) {
							if ((index + 1) > this.getLiveblog().data('showEntries')) {
								new_post.find('> li').addClass('elb-hide elb-liveblog-initial-post');
								
								this.getElement('load_more_button').show();
							}

							if (post.id === this.getLiveblog().data('highlightedEntry')) {
								go_to = true;

								new_post.find('> li').addClass('elb-liveblog-highlight');
								new_post.find('> li').removeClass('elb-hide');
							}

							this.getElement('list').append(new_post.html());

							if (go_to) {
								$(document).scrollTop(this.getElement('list').find('> li[data-elb-post-id="' + post.id + '"]').offset().top);
							}

							return;
						}

						if (!this.first_load && current_post.length != 0) {
							current_post.find('time').replaceWith(new_post.find('time'));
							current_post.find('.elb-liveblog-post-heading').replaceWith(new_post.find('.elb-liveblog-post-heading'));

							// Update content only if it doesn't contain iframe's due to possible layout shifts.
							if (current_post.find('.elb-liveblog-post-content iframe').length == 0) {
								current_post.find('.elb-liveblog-post-content').replaceWith(new_post.find('.elb-liveblog-post-content'));
							}

							return;
						}

						if (!this.first_load && current_post.length == 0) {
							new_post.find('li').addClass('elb-new');

							new_post.find('li').hide();

							this.new_posts = this.new_posts + 1;
							
							this.getElement('list').prepend(new_post.html());

							return;
						}
					});

					typeof elb_after_feed_load === 'function' && elb_after_feed_load(feed);

					this.first_load = false;

					if (this.getElement('list').find('> li').length == 0) {
						$('.elb-no-liveblog-entries-message').show();
					}

					if (this.new_posts > 0) {
						$(document).find('title').text('(' + this.new_posts + ') ' + this.document_title);

						var elb_update_message;

						if (this.new_posts === 1) {
							elb_update_message = elb.new_post_msg.replace("%s", this.new_posts);
						} else {
							elb_update_message = elb.new_posts_msg.replace("%s", this.new_posts);
						}

						this.getElement('show_new_button').show().text(elb_update_message);

						typeof elb_after_update_liveblog_callback === 'function' && elb_after_update_liveblog_callback();
					}

					if (feed.status === 'closed') {
						this.getElement('status_message').show();
					} else {
						setTimeout(() => { this.fetch() }, elb.interval * 1000);
					}
				}
			});
		},

		getTime: function() {
			var d = new Date();
			var time = d.getTime();

			if (time === 0) {
				return 0;
			}
			return Math.round(time / 60000);
		},

		getEndpoint: function() {
			var url = this.getLiveblog().data('endpoint');

			if (this.getLiveblog().data('appendTimestamp')) {
				url = url + '?_=' + this.getTime();
			}

			return url;
		},
		
		showNew: function() {
			this.getElement('list').find('> li.elb-new').not(':visible').fadeIn();
			
			this.new_posts = 0;
			
			this.getElement('show_new_button').hide();
			
			$(document).find('title').text(this.document_title);
		},
		
		loadMore: function() {
			var liveblog = this.getLiveblog();
			
			this.getElement('list').find('> li.elb-hide.elb-liveblog-initial-post').each(function (index, post) {
				if (liveblog.data('showEntries') > index) {
					$(this).removeClass('elb-hide');
				}
			});
			
			if (this.getElement('list').find('> li.elb-hide.elb-liveblog-initial-post').length == 0) {
				this.getElement('load_more_button').text(elb.now_more_posts).delay(2000).fadeOut(1000);
			}
			
			typeof elb_after_load_more_callback === 'function' && elb_after_load_more_callback();
		}	
	}

	elb_liveblog.init();
});