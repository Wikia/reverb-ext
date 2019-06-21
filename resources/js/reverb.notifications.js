
(function(){ $.when( mw.loader.using(['mediawiki.api', 'mediawiki.jqueryMsg']), $.ready).then(function() {
    /***
     *    ██████╗ ███████╗██╗   ██╗███████╗██████╗ ██████╗ 
     *    ██╔══██╗██╔════╝██║   ██║██╔════╝██╔══██╗██╔══██╗
     *    ██████╔╝█████╗  ██║   ██║█████╗  ██████╔╝██████╔╝
     *    ██╔══██╗██╔══╝  ╚██╗ ██╔╝██╔══╝  ██╔══██╗██╔══██╗
     *    ██║  ██║███████╗ ╚████╔╝ ███████╗██║  ██║██████╔╝
     *    ╚═╝  ╚═╝╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝╚═════╝ 
     *           We out here using jQuery in 2019.                              
     */

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

    /**
     *  Identify user box to place notifications directly next to it.
     *  Also remove any echo notification boxes that may exist.
     */
    var userBox;
    $('.netbar-box').each(function(){
        nbx = $(this);
        if (nbx.hasClass('echo')) {
            //nbx.hide();
            lastRemoved = nbx;
        }
        if (nbx.hasClass('user')) {
            userBox = nbx;
        }
    });
    
    // Check if we are inside the notification page.
    // There may be a more "mediawiki" was of doing this...
    var notificationPage = false;
    if (window.location.pathname == "/Special:Notifications" ||
        window.location.pathname == "/index.php" && window.location.search.indexOf('title=Special:Notifications') !== -1) {
        notificationPage = true;
    }
   
    /**
     * Setup "control functions"
     */
    var updateCounts = function(npo) {
        var total = meta.total_all;
        var totalUnread = meta.unread;
        var totalRead = meta.read;

        var notificationPageOnly = npo ? npo : false;

        $("#reverb-ru-all").html( mw.msg('special-button-all',total) );
        $("#reverb-ru-read").html( mw.msg('special-button-read',totalRead) );
        $("#reverb-ru-unread").html( mw.msg('special-button-unread',totalUnread) );

        if (!notificationPageOnly) {
            $(".reverb-total-notifications").html(totalUnread);
            if (totalUnread > 0) {
                $('.reverb-bell').addClass('reverb-bell-unread');
            } else {
                $('.reverb-bell').removeClass('reverb-bell-unread');
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

    var initPanel = function() {
        log('Injecting HTML.');
        var notificationButton = buildNotificationButton();
        var notificationPanel = buildNotificationPanel({globalNotifications: false});
        notificationPanel.appendTo('body');
        notificationButton.insertBefore(userBox);

        // TODO: Allow hover state and click state. Some work needs to be done here for that to function.

        notificationButton.on('hover mouseover', function(){
            //notificationPanel.show();
        });
        notificationButton.on('click', function(){
            notificationPanel.toggle();
        });
        
        $('#global-wrapper').on('click',function(){
            notificationPanel.hide();
        });
        
        var panelTotal = 10;

        loadNotifications({page: 0, perpage: panelTotal, unread: 1},function(data){
            updateCounts();
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
        });
    }

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
        }

        var data = {
            action:'notifications', 
            do:'getNotificationsForUser', 
            page: f.page, 
            itemsPerPage: f.perpage,
            format:'json'
        };

        if (f.type) {
            data.type = f.type;
        }
        if (f.unread) {
            data.unread = f.unread;
        }
        if (f.read) {
            data.read = f.read;
        }

        api.get(data)
        .done(function(data) {
            if (data.meta) {
                meta = data.meta;
            }
            if (data.notifications && data.notifications.length) {
                console.log(data.notifications);
                cb(data)
            }
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
            var icon = (n.icons.notification && n.icons.notificaton !== ".svg") ? n.icons.notification : ( (n.icons.subcategory && n.icons.subcategory !== ".svg" ) ? n.icons.subcategory : ((n.icons.category && n.icons.category !== ".svg") ? n.icons.category : false));
            icon = icon ? icon : "feedback.svg"; // set a default fallback icon

            // Convert for javascript
            var created_at = n.created_at * 1000;
            var created = moment(created_at).fromNow();
            
            // Handle Read Count -- Not available from API yet
            var wasRead = n.dismissed_at ? true : false;
            var read = wasRead ? "read" : "unread";

            var notificationData = {
                id: n.id,
                header: compact ? header : longheader,
                body: message,
                read: read,
                icon: icon,
                created: created
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
    var markRead = function(id){
        api.post({action:'notifications', do:'dismissNotification', notificationId: id, format:'json', formatversion: 2})
        .done(function(data) {
            console.log(data);
            if (data.success) {
                // If marked read, remove the little bubblyboi
                $(".reverb-npnr-unread[data-id='"+id+"']").addClass('reverb-nrpr-read').removeClass('reverb-npnr-unread');
                $(".reverb-npn-row[data-id='"+id+"']").fadeOut();

                console.log(meta);
                meta.unread = meta.unread - 1;
                meta.read = meta.read + 1;
                updateCounts();

                
            } else {
                console.log('There was an issue dismissing id '+id);
            }
        });

    }

    $(document).on('click', ".reverb-npnr-unread", function(){
        var nId = $(this).closest(".reverb-npn-row").data("id");
        markRead(nId);
    })

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

    if (notificationPage) {

        var perPage = 10;
        var activeFilters = {};

        // Mark All as Read button
        $("#reverb-mark-all-read").click(function(){
            alert("RIP");
        });

        $(".reverb-filter-checkbox").change(function() {
            console.log(this.id + " changed");
            if (this.id == "filter_all") {
                // This is the all checkbox. Lets uncheck every other box
                $('.reverb-filter-checkbox').each(function () { 
                    if (this.id !== "filter_all") {
                        this.checked = false; 
                    }
                });
                generateWithFilters({page: 0, perpage: perPage}, false);
                $(".reverb-active-button").removeClass('reverb-active-button');
                $("#reverb-ru-all").addClass('reverb-active-button');
            } else {
                // A different filter was clicked.
                $('#filter_all').get(0).checked = false;
                var checked = $('.reverb-filter-checkbox:checked');
                var filters = [];
                for (var x in checked) {
                    if (checked[x].id) {
                        var filter = checked[x].id.toString().replace("filter_","");
                        filters.push(filter);
                    }
                }

                generateWithFilters({page: 0, perpage: perPage, type: filters.join(',')}, false);
                $(".reverb-active-button").removeClass('reverb-active-button');
                $("#reverb-ru-all").addClass('reverb-active-button');
            }
        });

        $("#reverb-ru-all").click(function(){
            generateWithFilters({page: 0, perpage: perPage}, true);
            $(".reverb-active-button").removeClass('reverb-active-button');
            $(this).addClass('reverb-active-button');
        });

        $("#reverb-ru-unread").click(function(){
            generateWithFilters({page: 0, perpage: perPage, unread: 1}, true);
            $(".reverb-active-button").removeClass('reverb-active-button');
            $(this).addClass('reverb-active-button');
        });

        $("#reverb-ru-read").click(function(){
            generateWithFilters({page: 0, perpage: perPage, read: 1}, true);
            $(".reverb-active-button").removeClass('reverb-active-button');
            $(this).addClass('reverb-active-button');
        });


        var generateWithFilters = function(filters, noUpdateCount) {
            activeFilters = filters;
            noUpdateCount = noUpdateCount ? true : false;
            loadNotifications(filters,function(data){
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
                }
                if (meta.total_all > meta.total_this_page) {
                    // Oh boy, we gotta do pagination guys
                    $(".reverb-notification-page-paging").pagination({
                        items: meta.total_all,
                        itemsOnPage: meta.items_per_page,
                        cssStyle: 'light-theme', // CSS has hydradark and hydra selectors in it
                        onPageClick: function(page,event) {
                            var newfilters = activeFilters;
                            newfilters.page = page-1;
                            console.log(newfilters);
                            loadNotifications(newfilters,function(data){
                                if (data.notifications && data.notifications.length) {
                                    $(".reverb-notification-page-notifications").empty();
                                    var notifications = buildNotificationsFromData(data,false);
                                    for (var x in notifications) {
                                        addNotification(notifications[x],'specialpage');
                                    }
                                }
                            });
                        }
                    });
                }
            });
        }
        generateWithFilters({page: 0, perpage: perPage});

    
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
        var html = '<div class="reverb-npn-row"><a class="reverb-npn-viewmore" href="/Special:Notifications">View '+ more +' Additional Unread <i class="fa fa-arrow-right"></i></button></div>';
        return $(html);
    }

    var buildNotification = function(d) {
        var html = ''
        + '<div class="reverb-npn-row" data-id="'+d.id+'">'
        + '    <div class="reverb-npnr-left">'
        + '        <img src="/extensions/Reverb/resources/icons/'+d.icon+'" class="reverb-icon" />'
        + '    </div>'
        + '    <div class="reverb-npnr-right">'
        + '        <div class="reverb-npnr-header">'+d.header+'</div>';
        if (d.body && d.body.length) {
           html += '<div class="reverb-npnr-body">'+d.body+'</div>'
        }
        html += '      <div class="reverb-npnr-bottom">'
        + '            <span class="reverb-npnr-'+d.read+'" data-id="'+d.id+'"></span>'
        + '            ' + d.created
        + '        </div>'
        + '    </div>'
        + '</div>';
        return $(html);
    }

    var buildNotificationPanel = function(data) {
        // lots of i18n stuff to add in here...
        var html = '<div class="reverb-np">'
                 + '    <div class="reverb-np-header">'
                 + '        <span class="reverb-nph-right"><a href="/Special:Notifications">View All <i class="fa fa-arrow-right"></i></a></span>'
                 + '        <span class="reverb-nph-notifications">Notifications (<span class="reverb-total-notifications">0</span>)</span>'
                 + '        <span class="reverb-nph-preferences"><i class="fa fa-cog"></i></span>'
                 + '    </div>';
            if (data.globalNotifications) {
                html += '<div class="reverb-npn-row reverb-npn-row-global">'
                      + '   <div class="reverb-npnr-left">'
                      + '       <img src="/extensions/Reverb/resources/icons/global.svg" class="reverb-icon reverb-icon-global">'
                      + '   </div>'
                      + '   <div class="reverb-npnr-right">'
                      + '       <div class="reverb-npnr-header">'
                      + '           3 unread notifications from other wikis. <i class="fa fa-chevron-down"></i>'
                      + '           <span class="reverb-npnr-unread reverb-npnr-unread-global"></span>'
                      + '       </div>'
                      + '   </div>'
                      + '</div>';
            }
            html +='    <div class="reverb-npn">'
                 + '        <div class="reverb-np-no-unread">No Unread Notifications</div>'
                 + '    </div>'
                 + '</div>'
        return $(html);
    }

    var buildNotificationButton = function(data) {
        var html = '<div class="netbar-box right reverb-notifications reverb-bell">'
                 + '    <i class="fa fa-envelope"></i>'
                 + '</div>'
        return $(html);
    }

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

   initPanel();

}); })();