
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
    var updateCounts = function(total, totalUnread, totalRead) {
        $("#reverb-ru-all").html( mw.msg('special-button-all',total) );
        $("#reverb-ru-read").html( mw.msg('special-button-read',totalRead) );
        $("#reverb-ru-unread").html( mw.msg('special-button-unread',totalUnread) );
        $(".reverb-total-notifications").html(totalUnread);
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
        notificationButton.on('click', function(){
            notificationPanel.toggle();
        });     
        
        loadNotifications(0,50,function(data){
            if (data.notifications && data.notifications.length) {
                var notifications = buildNotificationsFromData(data,true);
                for (var x in notifications) {
                    addNotification(notifications[x]);
                }
            }
        });
    }

    var loadNotifications = function(page, perpage, cb) {
        if (!page) page = 0;
        if (!perpage) perpage = 50;

        api.get({action:'notifications', do:'getNotificationsForUser', page: page, itemsPerPage: perpage, format:'json'})
        .done(function(data) {
            if (data.notifications && data.notifications.length) {
                cb(data)
            }
        });
    }

    var buildNotificationsFromData = function(data, compact) {
        // build content for panel
        var unread = 0;
        var total = data.notifications.length;
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
            if (!wasRead) { unread++; } 
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
        // @TODO maybe move this somewhere else?
        updateCounts(total, unread, (total - unread));
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
        alert("Eventually, this will mark id "+id+" as read.");
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

        // Mark All as Read button
        $("#reverb-mark-all-read").click(function(){
            alert("Not implamented yet.");
        });


        loadNotifications(0,5,function(data){
            if (data.notifications && data.notifications.length) {
                console.log(data.notifications);
                var notifications = buildNotificationsFromData(data,false);
                for (var x in notifications) {
                    addNotification(notifications[x],'specialpage');
                }
            }
        });

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
        + '            <span class="reverb-npnr-'+d.read+'"></span>'
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
        var html = '<div class="netbar-box right reverb-notifications">'
                 + '    <i class="fa fa-envelope"></i> <span class="reverb-total-notifications"></span>'
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