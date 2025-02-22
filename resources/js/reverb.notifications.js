
(function(){ $.when( mw.loader.using(['mediawiki.api', 'mediawiki.jqueryMsg']), $.ready).then(function() {
	const perPage = 10;
	/***
	 *    ██████╗ ███████╗██╗   ██╗███████╗██████╗ ██████╗
	 *    ██╔══██╗██╔════╝██║   ██║██╔════╝██╔══██╗██╔══██╗
	 *    ██████╔╝█████╗  ██║   ██║█████╗  ██████╔╝██████╔╝
	 *    ██╔══██╗██╔══╝  ╚██╗ ██╔╝██╔══╝  ██╔══██╗██╔══██╗
	 *    ██║  ██║███████╗ ╚████╔╝ ███████╗██║  ██║██████╔╝
	 *    ╚═╝  ╚═╝╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝╚═════╝
	 *           We out here using jQuery in 2019.
	 */

	var devNotice = function(msg) {
		alert("[Reverb Development Notice] "+msg);
		console.log('[REVERB DEV NOTE]',msg);
	}

	var l = function(v,v2) {
		return mw.message(v,v2).plain();
	}

	var api = new mw.Api();
	window.log = function(...args){
		mw.log('[REVERB]', ...args);
	}
	log('Display Logic Loaded.');

	// Update this with every API call for accurate meta tracking
	var meta = {
		"unread": 0,
		"read": 0,
		"total_this_page": 0,
		"total_all": 0,
		"page": 0,
		"items_per_page": 0
	}

	let currentMeta = {
		"unread": 0,
		"read": 0,
		"total_this_page": 0,
		"total_all": 0,
		"page": 0,
		"items_per_page": 0
	};

	/**
	 *  Identify user box to place notifications directly next to it.
	 *  Also remove any echo notification boxes that may exist.
	 */



	var getUserBox = function() {
		var userBox;
		$('.netbar-box').each(function(){
			nbx = $(this);
			if (nbx.hasClass('user')) {
				userBox = nbx;
			}
		});
		if (typeof userBox == 'undefined') {
			userBox = false;
		}
		return userBox;
	}

	const IS_FANDOM_DESKTOP = mw.config.get('skin') === 'fandomdesktop';
	var setButtonActive = function (target) {
		const activeButtonClass = IS_FANDOM_DESKTOP ? 'wds-is-current' : 'reverb-active-button';
		const button = IS_FANDOM_DESKTOP ? target.parent() : target;

		$('.reverb-button-bar .' + activeButtonClass).removeClass(activeButtonClass);
		button.addClass(activeButtonClass);
	};

	reverbNotificationPage = (typeof window.reverbNotificationPage !== "undefined") ? true : false;
	log('Notification Page: ' + reverbNotificationPage);

	/**
	 * Setup "control functions"
	 */
	var updateCounts = function(npo) {
		var total = meta.total_all;
		var totalUnread = meta.unread;
		var totalRead = meta.read;

		var reverbNotificationPageOnly = npo ? npo : false;

		$("#reverb-ru-all").html( mw.msg('special-button-all',total) );
		$("#reverb-ru-read").html( mw.msg('special-button-read',totalRead) );
		$("#reverb-ru-unread").html( mw.msg('special-button-unread',totalUnread) );

		if (!reverbNotificationPageOnly) {
			$(".reverb-total-notifications").html(totalUnread);
			if (totalUnread > 0) {
				$('.reverb-bell').addClass('reverb-bell-unread');
				$('.reverb-bell-notification-count').show();
			} else {
				$('.reverb-bell').removeClass('reverb-bell-unread');
				$('.reverb-bell-notification-count').hide();
			}
		}
	};

	var addNotification = function(notification, target) {
		// hide if we are adding a notification
		switch (target) {
			case "dropdown":
			default:
				selector = '.reverb-npn';
				break;
			case "specialpage":
				selector = '.reverb-notification-page-notifications';
				break;
		}
		$('.reverb-np-no-unread').hide();
		notification.appendTo(selector);
	}

	/**
	 * Inject fake notification data until we have real data.
	 */

	var initNotifications = function() {
		log('Injecting HTML.');
		var userBox = getUserBox();
		if (userBox) {
			var notificationButton = buildNotificationButton();
			var notificationPanel = buildNotificationPanel({globalNotifications: false});
			notificationPanel.appendTo('body');
			notificationButton.insertBefore(userBox);

			$('.netbar-box.has-drop').on('mouseover', function(){
				notificationPanel.hide();
				$(".reverb-np-arrow").hide();
			});

			$(document).on('mouseup',function(e){
				var target = $(e.target);
				if (notificationButton.is(e.target) || notificationPanel.is(e.target) || target.hasClass('reverb-ddt') || target.parents('.reverb-np').length > 0) {
					notificationPanel.show();
					$(".reverb-np-arrow").show();
				} else {
					notificationPanel.hide();
					$(".reverb-np-arrow").hide();
				}
			})

			var panelTotal = 10;

			loadNotifications({page: 0, perpage: panelTotal},function(data){
				updateCounts();
				if (reverbNotificationPage) {
					initialSpecialPageData();
				}
				if (data.notifications && data.notifications.length) {
					var notifications = buildNotificationsFromData(data,true);
					for (var x in notifications) {
						addNotification(notifications[x]);
					}

					if (meta.unread > panelTotal) {
						addNotification(
							buildViewMore( meta.unread - panelTotal )
						)
					}
				}
			})
		} else {
			// If we cant find the userbox, lets assume we are mobile.
			var mheader = $("form.header");
			loadNotifications({page: 0, perpage: 1},function(data){
				if (reverbNotificationPage) {
					initialSpecialPageData();
				}
				buildMobileIcon(data.meta.unread).appendTo(mheader);
			});
		}
	}

	let metaHasSet = false;

	var loadNotifications = function(filters, cb) {

		var f = {
			page: 0,
			perpage: 50,
			//unread: 1,
			//read: 1,
			//type: null
		}

		for (var x in filters) {
			f[x] = filters[x];
			metaHasSet = false;
		}

		var data = {
			action:'notifications',
			do:'getNotificationsForUser',
			page: f.page,
			itemsPerPage: f.perpage,
			format:'json'
		};

		var urlParams = new URLSearchParams(document.location.search);

		if (urlParams.has('uselang')) {
			data.uselang = urlParams.get('uselang');
		}

		let refreshMeta = true;

		if (f.type) {
			data.type = f.type;
		}
		if (f.unread) {
			data.unread = f.unread;
			refreshMeta = false;
		}
		if (f.read) {
			data.read = f.read;
			refreshMeta = false;
		}

		api.get(data)
			.done(function(data) {
				if (data.meta && refreshMeta && !metaHasSet) {
					meta = data.meta;
					metaHasSet = true;
				}
				currentMeta = data.meta;
				cb(data)
			});
	}

	var buildNotificationsFromData = function(data, compact) {
		// build content for panel
		var notifications = [];
		for (var x in data.notifications) {
			var n = data.notifications[x];

			// Setup header
			var header = n.header_short ? n.header_short : false;
			var longheader = n.header_long ? n.header_long : (n.header_short ? n.header_short : false);

			// Setup message body
			var message = n.user_note ? n.user_note : "";

			// Try Notification, then Subcategory, then Category...
			var icon = (n.icons.notification) ? n.icons.notification : ( (n.icons.subcategory && n.icons.subcategory) ? n.icons.subcategory : ((n.icons.category && n.icons.category) ? n.icons.category : false));
			icon = icon ? icon : "fa-bullhorn";

			// Set localization for moment
			var lang = mw.config.get('wgContentLanguage');
			moment.locale(lang);

			// Convert for javascript
			var created_at = moment(n.created_at * 1000);
			var created = created_at.fromNow();
			var timestamp = created_at.format("dddd, MMMM Do YYYY, h:mm:ss a");

			var site_name = !n.site_name ? 'Unknown Wiki' : n.site_name;
			var site_url = !n.origin_url ? '' : n.origin_url.replace(/\/$/,"");

			// Handle Read Count -- Not available from API yet
			var wasRead = n.dismissed_at ? true : false;
			var read = wasRead ? "read" : "unread";

			var notificationData = {
				id: n.id,
				header: compact ? header : longheader,
				body: message,
				read: read,
				icon: icon,
				created: created,
				timestamp: timestamp,
				site_name: site_name,
				site_url: site_url
			};

			notifications.push(buildNotification(notificationData));
		}
		return notifications;
	}


	/***
	 *    ███████╗██╗   ██╗███████╗███╗   ██╗████████╗    ██╗  ██╗ █████╗ ███╗   ██╗██████╗ ██╗     ██╗███╗   ██╗ ██████╗
	 *    ██╔════╝██║   ██║██╔════╝████╗  ██║╚══██╔══╝    ██║  ██║██╔══██╗████╗  ██║██╔══██╗██║     ██║████╗  ██║██╔════╝
	 *    █████╗  ██║   ██║█████╗  ██╔██╗ ██║   ██║       ███████║███████║██╔██╗ ██║██║  ██║██║     ██║██╔██╗ ██║██║  ███╗
	 *    ██╔══╝  ╚██╗ ██╔╝██╔══╝  ██║╚██╗██║   ██║       ██╔══██║██╔══██║██║╚██╗██║██║  ██║██║     ██║██║╚██╗██║██║   ██║
	 *    ███████╗ ╚████╔╝ ███████╗██║ ╚████║   ██║       ██║  ██║██║  ██║██║ ╚████║██████╔╝███████╗██║██║ ╚████║╚██████╔╝
	 *    ╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═══╝   ╚═╝       ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ ╚══════╝╚═╝╚═╝  ╚═══╝ ╚═════╝
	 *    Like we could actually handle anything. What a joke.
	 *
	 */

		// Handle marking events as read!
	var markRead = function(id, unread){
			unread = unread ? true : false;
			var opts = {action:'notifications', do:'dismissNotification', notificationId: id, format:'json', formatversion: 2};
			if (unread) {
				opts.dismissedAt = 0;
			}

			api.post(opts)
				.done(function(data) {
					if (data.success) {
						if (unread) {
							$(".reverb-npnrc[data-id='"+id+"']").addClass('reverb-npnr-unread').removeClass('reverb-npnr-read');
							$(".reverb-npn-row[data-id='"+id+"']").addClass('reverb-npn-row-unread');
							meta.unread = meta.unread + 1;
							meta.read = meta.read - 1;
						} else {
							$(".reverb-npnrc[data-id='"+id+"']").addClass('reverb-npnr-read').removeClass('reverb-npnr-unread');
							$(".reverb-npn-row[data-id='"+id+"']").removeClass('reverb-npn-row-unread');
							meta.unread = meta.unread - 1;
							meta.read = meta.read + 1;
						}
						updateCounts();
					} else {
						log('There was an issue with api call for id '+id);
					}
				});
		}

	// Mark notification as read if we click a link in the notification.
	$(document).on('click', ".reverb-npnr-header > a, .reverb-npnr-body > a", function(e){
		var id = $(this).parent().data('id');
		markRead(id);
	});

	// Mark notifications as read when you click the notification indicator.
	$(document).on('click', ".reverb-npnr-hitbox", function(){
		var nId = $(this).closest(".reverb-npn-row").data("id");
		var button = $(this).find('.reverb-npnrc')[0];
		if ($(button).hasClass('reverb-npnr-unread')) {
			markRead(nId);
		} else {
			markRead(nId,true);
		}
	});

	$(document).on('click', "#reverb-mark-all-read-panel", function(){
		api.post(
			{
				action: 'notifications',
				do: 'dismissAllNotifications',
				format: 'json',
				formatversion: 2
			}
		).done(function(data) {
			if (reverbNotificationPage) {
				generateWithFilters(
					{
						page: 0,
						perpage: perPage
					},
					false
				);
			}
			$('.reverb-npn > .reverb-npn-row').each(function(){
				$(this).hide();
			});
			$('.reverb-np-no-unread').show();
			meta.read = meta.read + meta.unread;
			meta.unread = 0;
			updateCounts();
		});
	});

	/***
	 *    ███████╗██████╗ ███████╗ ██████╗██╗ █████╗ ██╗         ██████╗  █████╗  ██████╗ ███████╗
	 *    ██╔════╝██╔══██╗██╔════╝██╔════╝██║██╔══██╗██║         ██╔══██╗██╔══██╗██╔════╝ ██╔════╝
	 *    ███████╗██████╔╝█████╗  ██║     ██║███████║██║         ██████╔╝███████║██║  ███╗█████╗
	 *    ╚════██║██╔═══╝ ██╔══╝  ██║     ██║██╔══██║██║         ██╔═══╝ ██╔══██║██║   ██║██╔══╝
	 *    ███████║██║     ███████╗╚██████╗██║██║  ██║███████╗    ██║     ██║  ██║╚██████╔╝███████╗
	 *    ╚══════╝╚═╝     ╚══════╝ ╚═════╝╚═╝╚═╝  ╚═╝╚══════╝    ╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚══════╝
	 *    The below code is only focused on the special pages, and will only get executed if it is
	 *    detected that we are on a special page.
	 *
	 *    This is not the *only* code that effects special pages -- its just code that only effects special pages.
	 */

	if (reverbNotificationPage) {
		let activeFilters = {};

		// Mark All as Read button
		$("#reverb-mark-all-read").click(function(){
			api.post({action:'notifications', do:'dismissAllNotifications', format:'json', formatversion: 2})
				.done(function(data) {
					generateWithFilters({page: 0, perpage: perPage}, false);
					if ($('#reverb-ru-unread').parents('li').hasClass('wds-is-current')) {
						$('#reverb-ru-read').click();
					}

					meta.read = meta.read + meta.unread;
					meta.unread = 0;
					updateCounts();
				});
		});

		// Make firefox work like a good web browser.
		$('.reverb-filter-checkbox').each(function(){
			this.checked = true;
		})

		$(".reverb-filter-checkbox").change(function(e) {
			// check original event to verify human interaction
			if (this.id == "filter_all" && this.checked) {

				// This is the all checkbox. Lets check every other box
				$('.reverb-filter-checkbox').each(function () {
					if (this.id !== "filter_all") {
						this.checked = true;
					}
				});
				generateWithFilters({page: 0, perpage: perPage}, false);
				setButtonActive($("#reverb-ru-all"));
			} else {

				if (e.originalEvent !== undefined) {

					if (this.id == "filter_all") {
						// we uncheked all, so uncheck everything else
						$('.reverb-filter-checkbox').each(function () {
							this.checked = false;
						});
					}
					// A different filter was clicked.
					$('#filter_all').get(0).checked = false;
					var checked = $('.reverb-filter-checkbox:checked');
					var filters = [];
					checked.each(function() {
						var types = $(this).attr('data-types').toString();
						if (types.length > 0) {
							var filter = types;
							filters.push(filter);
						}
					});
					if (!checked.length) {
						//$("#filter_all").click();
						$(".reverb-notification-page-paging").empty();
						$(".reverb-notification-page-notifications").empty();
						addNotification(buildNoNotifications(),'specialpage');
						$("#reverb-ru-all").html( mw.msg('special-button-all', 0) );
						$("#reverb-ru-read").html( mw.msg('special-button-read', 0) );
						$("#reverb-ru-unread").html( mw.msg('special-button-unread', 0) );
					} else {
						if (checked.length == $('.reverb-filter-checkbox').length - 1) {
							// if all are checked (except for all) then check all
							$('#filter_all').get(0).checked = true;
						}

						generateWithFilters({page: 0, perpage: perPage, type: filters.join(',')}, false);
						setButtonActive($("#reverb-ru-all"));
					}
				}
			}
		});

		const makeNewFilter = function () {
			const newFilters = activeFilters;
			newFilters.page = 0;
			newFilters.perpage = perPage;
			return newFilters;
		};

		const makeEmptyBox = function (showingRead, buttonActive) {
			$(".reverb-notification-page-paging").empty();
			$(".reverb-notification-page-notifications").empty();
			addNotification(buildNoNotifications(showingRead),'specialpage');
			setButtonActive(buttonActive);
		};

		const isAllFiltersNotChecked = function() {
			return $(".reverb-filter-row input:checkbox:checked").length === 0
		};

		$("#reverb-ru-all").click(function(){
			if (isAllFiltersNotChecked()) {
				makeEmptyBox(false, $(this));
				return;
			}
			const newFilters = makeNewFilter();
			delete(newFilters.read);
			delete(newFilters.unread);
			generateWithFilters(newFilters, true);
			setButtonActive($(this));
		});

		$("#reverb-ru-unread").click(function(e){

			if (isAllFiltersNotChecked()) {
				makeEmptyBox(false, $(this));
				return;
			}

			const newFilters = makeNewFilter();
			newFilters.unread = 1;
			delete(newFilters.read);
			generateWithFilters(newFilters, true);
			setButtonActive($(this));
		});

		$("#reverb-ru-read").click(function(){

			if (isAllFiltersNotChecked()) {
				makeEmptyBox(true, $(this));
				return;
			}

			const newFilters = makeNewFilter();
			newFilters.read = 1;
			delete(newFilters.unread);
			generateWithFilters(newFilters, true);
			setButtonActive($(this));
		});


		var generateWithFilters = function(filters, noUpdateCount) {
			activeFilters = filters;
			noUpdateCount = !!noUpdateCount;
			var showingRead = false;

			if (typeof filters.read !== "undefined" && filters.read) {
				showingRead = true;
			}
			const getNumberOfItemsInViewedCategory = function(filters){
				if (filters.unread === 1) {
					return meta.unread;
				} else if (filters.read === 1) {
					return meta.read;
				} else {
					return meta.total_all;
				}
			}

			loadNotifications(filters, function(data) {
				if (!noUpdateCount) {
					updateCounts(true);
				}
				if (data.notifications && data.notifications.length) {
					$(".reverb-notification-page-paging").empty();
					$(".reverb-notification-page-notifications").empty();
					var notifications = buildNotificationsFromData(data,false);
					for (var x in notifications) {
						addNotification(notifications[x],'specialpage');
					}

					if (currentMeta.total_all > currentMeta.total_this_page) {
						// Oh boy, we gotta do pagination guys
						$(".reverb-notification-page-paging").pagination({
							items: currentMeta.total_all,
							itemsOnPage: currentMeta.items_per_page,
							cssStyle: 'light-theme', // CSS has hydradark and hydra selectors in it
							onPageClick: function(page,event) {
								// this refers to pagination object
								const newfilters = activeFilters;
								// when user un/read too many messages the pagination might end earlier
								const itemsInViewedCategory = getNumberOfItemsInViewedCategory(newfilters);
								const itemsInCategoryDecreased = this.items > itemsInViewedCategory;
								// edge case, if user dismissed notices and want next page, to avoid having gap just refresh current page
								const userExpectsNextPage = page === this.currentPage + 1;
								const pagesInViewedCategory = Math.ceil(itemsInViewedCategory / perPage);
								// if user clicks on page 4 and that page is no longer available because of dismissals we will put him on page 3
								newfilters.page = Math.min(
									itemsInCategoryDecreased && userExpectsNextPage ? this.currentPage : page,
									itemsInViewedCategory
								) - 1; // -1 because we index pages from 0, so first page is pages[0]

								loadNotifications(newfilters, function (data) {
									if (data.notifications && data.notifications.length) {
										$('.reverb-notification-page-notifications').empty();
										var notifications = buildNotificationsFromData(data, false);
										for (var x in notifications) {
											addNotification(notifications[x], 'specialpage');
										}
									}
								});

								// update values for pagination
								this.items = itemsInViewedCategory;
								// pass down computed page value to pagination (paginator count from 1 here so 1,2,3...)
								this.currentPage = newfilters.page + 1;
								// redraw pagination if number of pages changed (destroy if one page left)
								if (this.pages !== pagesInViewedCategory) {
									this.pages = pagesInViewedCategory;
									const elem = $('.reverb-notification-page-paging');
									this.pages === 1 ? elem.pagination('destroy') : elem.pagination('selectPage', this.currentPage);
								}
							}
						});
					}
				} else {
					// We need to display a "no items" section.
					$(".reverb-notification-page-paging").empty();
					$(".reverb-notification-page-notifications").empty();
					addNotification(buildNoNotifications(showingRead),'specialpage');
				}
			});
		}

		var initialSpecialPageData = function() {
			// Force filters reset back to "All" and repopulate.
			$(".reverb-filter-checkbox").change();
		}
	}

	/***
	 *    ████████╗███████╗███╗   ███╗██████╗ ██╗      █████╗ ████████╗███████╗███████╗
	 *    ╚══██╔══╝██╔════╝████╗ ████║██╔══██╗██║     ██╔══██╗╚══██╔══╝██╔════╝██╔════╝
	 *       ██║   █████╗  ██╔████╔██║██████╔╝██║     ███████║   ██║   █████╗  ███████╗
	 *       ██║   ██╔══╝  ██║╚██╔╝██║██╔═══╝ ██║     ██╔══██║   ██║   ██╔══╝  ╚════██║
	 *       ██║   ███████╗██║ ╚═╝ ██║██║     ███████╗██║  ██║   ██║   ███████╗███████║
	 *       ╚═╝   ╚══════╝╚═╝     ╚═╝╚═╝     ╚══════╝╚═╝  ╚═╝   ╚═╝   ╚══════╝╚══════╝
	 *  Imagine we are using template engines instead of just writing html into javascript.
	 *
	 */

	var buildViewMore = function(more) {
		var notificationsUrl = new mw.Title('Special:Notifications').getUrl();
		var html = '<div class="reverb-npn-row"><a class="reverb-npn-viewmore" href="' + notificationsUrl + '">'+mw.message('view-additional-unread', more).text()+' <i class="fa fa-arrow-right"></i></button></div>';
		return $(html);
	}

	var buildNoNotifications = function(showingRead) {
		var langStr = showingRead ? 'no-read' : 'no-unread';
		var html = '<div class="reverb-no-notifications">'+l(langStr)+'</div>';
		return $(html);
	}

	var buildNotification = function(d) {
		var extra = d.read == "unread" ? " reverb-npn-row-unread" : "";
		var html = ''
			+ '<div class="reverb-npn-row'+extra+'" data-id="'+d.id+'">'
			+ '    <div class="reverb-npnr-left">'
			+ '        <i class="fa '+d.icon+' fa-lg reverb-icon"></i>'
			+ '    </div>'
			+ '    <div class="reverb-npnr-right">'
			+ '        <div class="reverb-npnr-header" data-id="'+d.id+'">'+d.header+'</div>';
		if (d.body && d.body.length) {
			html += '<div class="reverb-npnr-body" data-id="'+d.id+'">'+d.body+'</div>'
		}
		html += '      <div class="reverb-npnr-bottom">'
			+ '            <span class="reverb-npnr-hitbox"><span class="reverb-npnr-'+d.read+' reverb-npnrc" data-id="'+d.id+'"></span></span>'
			+ '            <span title="'+d.timestamp+'">' + d.created + '</span>'
			+ '            <span class="reverb-npnrb-site">on <a href="'+d.site_url+'/Special:Notifications">'+d.site_name+'</span>'
			+ '        </div>'
			+ '    </div>'
			+ '</div>';
		return $(html);
	}

	var buildNotificationPanel = function(data) {
		var notificationUrl = new mw.Title('Special:Notifications').getUrl();
		var preferencesUrl = new mw.Title('Special:Preferences#mw-prefsection-reverb').getUrl();

		// lots of i18n stuff to add in here...
		var html = '<div class="reverb-np">'
			+ '    <div class="reverb-np-header">'
			+ '        <span class="reverb-nph-right"><span id="reverb-mark-all-read-panel">' + l('special-button-mark-all-read') + '</span></span>'
			+ '        <span class="reverb-nph-notifications"><a href="' + notificationUrl + '">'+ l('notifications') +' (<span class="reverb-total-notifications">0</span>)</a></span>'
			+ '        <span class="reverb-nph-preferences"><a href="' + preferencesUrl + '"><i class="fa fa-cog"></i></a></span>'
			+ '    </div>'
			+ '    <div class="reverb-npn">'
			+ '        <div class="reverb-np-no-unread">'+l('no-unread')+'</div>'
			+ '    </div>'
			+ '</div>'
		return $(html);
	}

	var buildNotificationButton = function(data) {
		var html = '<div class="netbar-box right reverb-notifications reverb-bell reverb-ddt">'
			+ '    <i class="fas fa-bell reverb-ddt"></i>'
			+ '	<span class="reverb-total-notifications reverb-bell-notification-count reverb-ddt"></span>'
			+ '	<div class="reverb-np-arrow"></div>'
			+ '</div>'
		return $(html);
	}

	var buildMobileIcon = function(unread) {
		var url = mw.Title.newFromText('Notifications', -1 ).getUrl();
		if (unread) {
			extra = ' reverb-mobile-bell-unread';
		} else {
			extra = '';
		}
		var html = '<div><a href="'+url+'" title="Notifications" class="mw-ui-icon mw-ui-icon-minerva-notifications mw-ui-icon-element user-button main-header-button'+extra+'" id="secondary-button"></a></div>'
		return $(html);
	}

	/***
	 *    ██╗  ██╗██╗ ██████╗██╗  ██╗    ██╗████████╗     ██████╗ ███████╗███████╗
	 *    ██║ ██╔╝██║██╔════╝██║ ██╔╝    ██║╚══██╔══╝    ██╔═══██╗██╔════╝██╔════╝
	 *    █████╔╝ ██║██║     █████╔╝     ██║   ██║       ██║   ██║█████╗  █████╗
	 *    ██╔═██╗ ██║██║     ██╔═██╗     ██║   ██║       ██║   ██║██╔══╝  ██╔══╝
	 *    ██║  ██╗██║╚██████╗██║  ██╗    ██║   ██║       ╚██████╔╝██║     ██║
	 *    ╚═╝  ╚═╝╚═╝ ╚═════╝╚═╝  ╚═╝    ╚═╝   ╚═╝        ╚═════╝ ╚═╝     ╚═╝
	 *    Now that we wrote a bunch of logic, lets kick off our actions.
	 */

	initNotifications();

	/*
		Developer:
			Let's replace echo with a nice
			alternative using modern tech
			and make it scalable across
			multiple platforms!

		MediaWiki:
			ResourceLoader is a steaming
			pile of crap and doesn't
			support any modern JavaScript
			so you just need to use jQuery.

		ResourceLoader:
			Don't even think about using
			ES6 stuff either!

		Developer:
		⢀⣠⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⠀⠀⠀⣠⣤⣶⣶
		⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀⠀⠀⢰⣿⣿⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⣀⣀⣾⣿⣿⣿⣿
		⣿⣿⣿⣿⣿⡏⠉⠛⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⣿
		⣿⣿⣿⣿⣿⣿⠀⠀⠀⠈⠛⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠿⠛⠉⠁⠀⣿
		⣿⣿⣿⣿⣿⣿⣧⡀⠀⠀⠀⠀⠙⠿⠿⠿⠻⠿⠿⠟⠿⠛⠉⠀⠀⠀⠀⠀⣸⣿
		⣿⣿⣿⣿⣿⣿⣿⣷⣄⠀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⣿⠏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠠⣴⣿⣿⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⡟⠀⠀⢰⣹⡆⠀⠀⠀⠀⠀⠀⣭⣷⠀⠀⠀⠸⣿⣿⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⠃⠀⠀⠈⠉⠀⠀⠤⠄⠀⠀⠀⠉⠁⠀⠀⠀⠀⢿⣿⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⢾⣿⣷⠀⠀⠀⠀⡠⠤⢄⠀⠀⠀⠠⣿⣿⣷⠀⢸⣿⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⡀⠉⠀⠀⠀⠀⠀⢄⠀⢀⠀⠀⠀⠀⠉⠉⠁⠀⠀⣿⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⣧⠀⠀⠀⠀⠀⠀⠀⠈⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢹⣿⣿
		⣿⣿⣿⣿⣿⣿⣿⣿⣿⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⣿
	*/
}); })();
