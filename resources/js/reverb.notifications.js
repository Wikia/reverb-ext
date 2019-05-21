(function(){

    window.log = function(){
        mw.log( Array.prototype.slice.call(arguments) );
    }

    /**
     *  Identify user box to place notifications directly next to it.
     *  Also remove any echo notification boxes that may exist.
     */

    var userBox;
    $('.netbar-box').each(function(){
        nbx = $(this);
        if (nbx.hasClass('echo')) {
            log('Removing Echo Netbar Item: ',nbx);
            nbx.hide();
            lastRemoved = nbx;
        }
        if (nbx.hasClass('user')) {
            userBox = nbx;
        }
    });

    /**
     *  Inject the new Reverb notification panel in the netbar;
     */

    var newpanel = $(`<div class="netbar-box right reverb-notifications">Notifications</div>`);
    newpanel.insertBefore(userBox);
    

    newpanel.on('click', function(){
    
        alert('NO NEW NOTIFICATIONS');


    });    



})();