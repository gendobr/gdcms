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
});