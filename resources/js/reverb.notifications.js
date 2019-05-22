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
            nbx.hide();
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

    var notificationPanel = $(`
        <div class="reverb-np">
            <div class="reverb-np-header"><i class="fa fa-envelope"></i> <span class="reverb-total-notifications">0</span> Unread Notifications</div>
            <div class="reverb-npn">
                <div class="reverb-np-no-unread">No Unread Notifications</div>
            </div>
            <div class="reverb-np-actions"></div>
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
        var extraClass = data.read?"reverb-npnr-read":"reverb-npnr-unread"; 
        var envelope = data.read?"fa-envelope-open":"fa-envelope";       

        return $(`
            <div class="reverb-npn-row ${extraClass}">
                <div class="reverb-npn-row-header"><i class="fa ${envelope}"></i> ${header}</div>
                <div class="reverb-npn-row-body">${body}</div>
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


    updateUnread(1);

    addNotification(
        buildNotification({
            header: "Wiki Page Deleted",
            body: "Something you cared about was destroyed.",
            read: false
        })
    );

    addNotification(
        buildNotification({
            header: "Wiki Page Changed",
            body: "Something you cared about was just changed to it was OK.",
            read: true
        })
    );


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