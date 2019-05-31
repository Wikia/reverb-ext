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
     * Globals or something. I dont know.
     */

    var icons = [
        "articleCheck.svg",
        "bell.svg",
        "edit.svg",
        "feedback.svg",
        "help.svg",
        "mention-failure.svg",
        "mention-success.svg",
        "message.svg",
        "revert.svg",
        "tray.svg",
        "user-speech-bubble.svg",
        "changes.svg",
        "edit-user-talk.svg",
        "global.svg",
        "link.svg",
        "mention-status-bundle.svg",
        "mention.svg",
        "notice.svg",
        "speechBubbles.svg",
        "user-rights.svg",
        "userTalk.svg"
    ];

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

    /**
     *  Inject the new Reverb notifications HTML.
     */
    log('Injecting HTML.');
   
    var notificationButton = $(`
        <div class="netbar-box right reverb-notifications">
            <i class="fa fa-envelope"></i> <span class="reverb-total-notifications">0</span>
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

    

    var notificationPanel = $(`
        <div class="reverb-np">
            <div class="reverb-np-header">
                <span class="reverb-nph-right">View All <i class="fa fa-arrow-right"></i></span>
                <span class="reverb-nph-notifications">Notifications (<span class="reverb-total-notifications">0</span>)</span>
                <span class="reverb-nph-preferences"><i class="fa fa-cog"></i></span>
            </div>
            ${globalNotifications}
            <div class="reverb-npn">
                <div class="reverb-np-no-unread">No Unread Notifications</div>
            </div>
        </div>
    `);
    
    notificationPanel.appendTo('body');
    notificationButton.insertBefore(userBox);
    
    notificationButton.on('click', function(){
        notificationPanel.toggle();
    });    

    /**
     * Setup "control functions"
     */

    var updateUnread = function(totalUnread) {
        $(".reverb-total-notifications").html(totalUnread);
    };

    var buildNotification = function(data) {
        var header = data.header;
        var body = data.body;
        var lastread = "1 day ago";
        var read = data.read ? "read" : "unread";
        var icon = data.icon;

        return $(`
            <div class="reverb-npn-row">
                <div class="reverb-npnr-left">
                    <img src="/extensions/Reverb/resources/icons/${icon}" class="reverb-icon" />
                </div>
                <div class="reverb-npnr-right">
                    <div class="reverb-npnr-header">${header}</div>
                    <div class="reverb-npnr-body">${body}</div>
                    <div class="reverb-npnr-bottom">
                        <span class="reverb-npnr-${read}"></span>
                        ${lastread}
                    </div>
                </div>
            </div>
        `);
    }

    var addNotification = function(notification) {
        // hide if we are adding a notification
        $('.reverb-np-no-unread').hide();
        notification.appendTo('.reverb-npn');
    }

    /**
     * Inject fake notification data until we have real data.
     */

    updateUnread(3);
    for (i = 0; i < 3; i++) { 
        addNotification(
            buildNotification({
                header: "Someone <b>ACTIONED</b> your edit on <b>SOMEWHERE</b>",
                body: "It was really great that it happened and I think you should be really happy about it. If you dont like it then you can just get over it. WOW. Really.",
                read: false,
                icon: icons[Math.floor(Math.random()*icons.length)]
            })
        );
    }

    for (i = 0; i < 5; i++) {      
        addNotification(
            buildNotification({
                header: "Your edit on <b>MEMES GALORE</b> was <b>BELETED</b>.",
                body: "On no. RIP.",
                read: true,
                icon: icons[Math.floor(Math.random()*icons.length)]
            })
        );
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