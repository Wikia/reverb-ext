(function(){

    /***
     *    ██████╗ ███████╗██╗   ██╗███████╗██████╗ ██████╗ 
     *    ██╔══██╗██╔════╝██║   ██║██╔════╝██╔══██╗██╔══██╗
     *    ██████╔╝█████╗  ██║   ██║█████╗  ██████╔╝██████╔╝
     *    ██╔══██╗██╔══╝  ╚██╗ ██╔╝██╔══╝  ██╔══██╗██╔══██╗
     *    ██║  ██║███████╗ ╚████╔╝ ███████╗██║  ██║██████╔╝
     *    ╚═╝  ╚═╝╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝╚═════╝ 
     *           We out here using jQuery in 2019.                              
     */

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
     *  Inject the new Reverb notifications HTML.
     */
    log('Injecting HTML.');
   
    var notificationButton = $(`
        <div class="netbar-box right reverb-notifications">
            <i class="fa fa-envelope"></i> <span class="reverb-total-notifications"></span>
        </div>
    `);

    var globalNotifications = `<div class="reverb-npn-row reverb-npn-row-global">
        <div class="reverb-npnr-left">
            <img src="/extensions/Reverb/resources/icons/global.svg" class="reverb-icon reverb-icon-global">
        </div>
        <div class="reverb-npnr-right">
            <div class="reverb-npnr-header">
                3 unread notifications from other wikis. <i class="fa fa-chevron-down"></i>
                <span class="reverb-npnr-unread reverb-npnr-unread-global"></span>
            </div>
        </div>
    </div>`;

    // clear it. We dont have support for this yet.
    globalNotifications = "";
    
    var notificationPanel = $(`
        <div class="reverb-np">
            <div class="reverb-np-header">
                <span class="reverb-nph-right"><a href="/Special:Notifications">View All <i class="fa fa-arrow-right"></i></a></span>
                <span class="reverb-nph-notifications">Notifications (<span class="reverb-total-notifications">0</span>)</span>
                <span class="reverb-nph-preferences"><i class="fa fa-cog"></i></span>
            </div>
            ${globalNotifications}
            <div class="reverb-npn">
                <div class="reverb-np-no-unread">No Unread Notifications</div>
            </div>
        </div>
    `);
    
    /**
     * Setup "control functions"
     */
    var updateUnread = function(totalUnread) {
        $(".reverb-total-notifications").html(totalUnread);
    };

    var buildNotification = function(d) {
        d.read = d.read ? "read" : "unread";
        d.created = moment(d.created).fromNow();
        return $(`
            <div class="reverb-npn-row">
                <div class="reverb-npnr-left">
                    <img src="/extensions/Reverb/resources/icons/${d.icon}" class="reverb-icon" />
                </div>
                <div class="reverb-npnr-right">
                    <div class="reverb-npnr-header">${d.header}</div>
                    <div class="reverb-npnr-body">${d.body}</div>
                    <div class="reverb-npnr-bottom">
                        <span class="reverb-npnr-${d.read}"></span>
                        ${d.created}
                    </div>
                </div>
            </div>
        `);
    }

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

    var api = new mw.Api();
    api.get({action:'notifications', do:'getNotificationsForUser', format:'json'})
    .done(function(data) {
        if (data.notifications && data.notifications.length) {

            // If we have data, lets injust the notification panel
            // Dont show notification panel at all on API failure. 
            notificationPanel.appendTo('body');
            notificationButton.insertBefore(userBox);
            notificationButton.on('click', function(){
                notificationPanel.toggle();
            });    
        
            // build content for panel
            var unread = 0;
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
                var created = n.created_at * 1000;

                // Handle Read Count -- Not available from API yet
                var read = n.dismissed_at ? true : false;
                if (!read) { unread++; } 
                
                var notification = buildNotification({
                    header: header,
                    body: message,
                    read: read,
                    icon: icon,
                    created: created
                });
                addNotification(notification);

                if (notificationPage) {
                    var notification2 = buildNotification({
                        header: longheader,
                        body: message,
                        read: read,
                        icon: icon,
                        created: created
                    });
                    addNotification(notification2,'specialpage');
                }
                
            }
            updateUnread(unread);
        }
    });

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
})();