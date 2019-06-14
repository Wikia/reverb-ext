
(function(){ $.when( mw.loader.using(['mediawiki.api', 'mediawiki.jqueryMsg']), $.ready).then(function() {
    
    window.log = function(...args){
        mw.log('[REVERB]', ...args);
    }
    log('Display Logic Loaded.');

    // Init the fancy pants templating library
    Twiggy.init(mw);
    
    var templates = {
        example: "/extensions/Reverb/resources/templates/example.twig",
        notification_panel: "/extensions/Reverb/resources/templates/notification_panel.twig",
        notification_button: "/extensions/Reverb/resources/templates/notification_button.twig",
        notification_row: "/extensions/Reverb/resources/templates/notification_row.twig"
    }
    var loadedTemplates = 0;

    for (var key of Object.keys(templates)) {
        Twig.twig({
            id: key,
            href: templates[key],
            load: function(template) {
                loadedTemplates++;
                checkTemplatesLoaded();
            }
        });
    }

    function checkTemplatesLoaded() {
        var totalTemplates = Object.keys(templates).length;
        log(loadedTemplates+"/"+totalTemplates+" templates loaded...");
        if (totalTemplates == loadedTemplates) {
            reverb();
        }
    }

    async function render(template,data) {
        if (!data) data = {};
        return await Twig.twig({ ref: template }).renderAsync(data);
    } 

    async function reverb() {
        /***
         *    ██████╗ ███████╗██╗   ██╗███████╗██████╗ ██████╗ 
         *    ██╔══██╗██╔════╝██║   ██║██╔════╝██╔══██╗██╔══██╗
         *    ██████╔╝█████╗  ██║   ██║█████╗  ██████╔╝██████╔╝
         *    ██╔══██╗██╔══╝  ╚██╗ ██╔╝██╔══╝  ██╔══██╗██╔══██╗
         *    ██║  ██║███████╗ ╚████╔╝ ███████╗██║  ██║██████╔╝
         *    ╚═╝  ╚═╝╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═╝╚═════╝ 
         *           We out here using jQuery in 2019.                              
         */

        /**
         *  Identify user box to place notifications directly next to it.
         *  Also remove any echo notification boxes that may exist.
         */

        var userBox;
        $('.netbar-box').each(function(){
            nbx = $(this);
            if (nbx.hasClass('echo')) {
                nbx.hide();
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
        var notificationButton = $(await render('notification_button'));
        var notificationPanel = $(await render('notification_panel',{ globalNotifications: false }));

        
        
        /**
         * Setup "control functions"
         */
        var updateCounts = function(total, totalUnread, totalRead) {
            $("#reverb-ru-all").html( mw.msg('special-button-all',total) );
            $("#reverb-ru-read").html( mw.msg('special-button-read',totalRead) );
            $("#reverb-ru-unread").html( mw.msg('special-button-unread',totalUnread) );
            $(".reverb-total-notifications").html(totalUnread);
        };

        var buildNotification = async function(d) {
            d.read = d.read ? "read" : "unread";
            d.created = moment(d.created).fromNow();
  
            
            return $(`
                <div class="reverb-npn-row" data-id="${d.id}">
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
                var total = data.notifications.length;
        
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

                    var notificationData = {
                        id: n.id,
                        header: header,
                        body: message,
                        read: read ? "read" : "unread",
                        icon: icon,
                        created: moment(created).fromNow()
                    };
                    
                    var render = await render('notification_row', notificationData);
                    console.log(render);

                    /*addNotification($(render));
                    
                    // If we are on a notification page, duplicate the data and change to long header
                    if (notificationPage) {
                        notificationData.header = longerheader;
                        addNotification($(await render('notification_row', notificationData)),'specialpage');
                    }*/
                }
                updateCounts(total, unread, (total - unread));
            }
        });

        var markRead = function(id){
            alert("Eventually, this will mark id "+id+" as read.");
        }

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


            $(document).on('click', ".reverb-npnr-unread", function(){
                var nId = $(this).closest(".reverb-npn-row").data("id");
                markRead(nId);
            })


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
    }
}); })();