$(window).load(function(){

    var setEqualHeight = function(list){
        var maxHeight=0;
	    list.each(function(ind, elm){
	    	var h=parseInt($(elm).height())+
	    	      parseInt($(elm).css('padding-top').replace(/\D/g,''))+
	    	      parseInt($(elm).css('padding-bottom').replace(/\D/g,''));
	    	if(h>maxHeight){
	    		maxHeight=h;
	    	}
	    })
	    list.css({height:(maxHeight+'px')});
    }
    
    if($(document).width()>=700){
        setEqualHeight($('.znu-2016-block-calendar-row').children());
	    setEqualHeight($('.znu-2016-block-anounces-one'));
    }
    window.toggle = function(selector){
    	if($(selector).hasClass('visible')){
            $(selector).removeClass('visible');
    	}else{
    		$(selector).addClass('visible')
    	}
    };
    
    
    
    // load carousel
    if($('#myCarousel').length > 0){
        var sourceUrl=$('#myCarousel').attr('data-source');
        if(sourceUrl){
            jQuery.get( sourceUrl, {}, function(data, textStatus, jqXHR){
                // Activate Carousel
                $("#myCarousel").html(data);
                $("#carhome").carousel();
            }, 'html' );
        }
    }
    if($('#frontPageNews').length > 0){
        var sourceUrl=$('#frontPageNews').attr('data-source');
        if(sourceUrl){
            jQuery.get( sourceUrl, {}, function(data, textStatus, jqXHR){
                // Activate Carousel
                $("#frontPageNews").html(data);
            }, 'html' );
        }
    }
    

    if(window.location.hash==='#printable'){
        var printDocument = window.document;
        //console.log(printDocument);
        printDocument.getElementById("znu-css-print").media="screen, print";
        printDocument.getElementById("znu-css-screen").media="none";
    }
});




function setCookie (name, value, expires, path, domain, secure) {
    document.cookie = name + "=" + escape(value) +
    ((expires) ? "; expires=" + expires : "") +
    ((path) ? "; path=" + path : "") +
    ((domain) ? "; domain=" + domain : "") +
    ((secure) ? "; secure" : "");
}

function getCookie(name) {
    var cookie = " " + document.cookie;
    var search = " " + name + "=";
    var setStr = null;
    var offset = 0;
    var end = 0;
    if (cookie.length > 0) {
        offset = cookie.indexOf(search);
        if (offset != -1) {
            offset += search.length;
            end = cookie.indexOf(";", offset)
            if (end == -1) {
                end = cookie.length;
            }
            setStr = unescape(cookie.substring(offset, end));
        }
    }
    return(setStr);
}


function setSkin(skinName){
    //console.log("css", skinName, { expires : 100 });
    setCookie ("css", skinName, 100, "/");
    window.location.reload();
}
