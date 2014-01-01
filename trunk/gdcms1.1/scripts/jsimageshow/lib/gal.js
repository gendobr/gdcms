var gallery;
function init_slider(gallerybox){

   // ------------- big image view - begin ---------------
   $('#'+gallerybox).after("<br/><span id="+gallerybox+"_bigimg class='bigimg'></span>");
   $('#'+gallerybox+'_bigimg').click(
        function(){
	        $(this).fadeOut(1000,
		        function(){
			        $(this).empty();
					// $(this).append('<marquee style="border: none; width: 30px;font-weight:bold;color:white;background-COLOR: #5774c2;" scrollamount="1" direction="left">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</marquee>');
				}
			);
		}
	);
   $('#'+gallerybox+'_bigimg').fadeOut();
   $('#'+gallerybox+'_bigimg').css('height',$(window).height());

   // ------------- big image view - end -----------------
   
   var tmp="";
   tmp+="$('#'+gallerybox+' a').each(";
   tmp+="  function(){";
   tmp+="      $(this).click( ";
   tmp+="        function(event){";
   tmp+="           cimg=$(this).attr('href');";
   tmp+="           $('#"+gallerybox+"_bigimg').fadeOut(1000,";
   tmp+="               function(){";
   tmp+="                  var el=$('#"+gallerybox+"_bigimg');";
   tmp+="                  el.empty();";
   tmp+="                  el.css('top',$(window).scrollTop());";
   tmp+="                  el.prepend(\"<img src=\"+cimg+\">\");";
   tmp+="                  el.fadeIn(1000);";
   tmp+="               }";
   tmp+="           );";
   tmp+="           return false;";
   tmp+="        }";
   tmp+="      );";

   tmp+="  }";
   tmp+=");";
   eval(tmp);
}

/*

var container='#container';
var gallery='#gallery';
var gallerybox='#gallerybox';
var btnleft="#left";
var btnright="#right";
var img_width=150;
var imgs=[];


var W,w,h,l,ID,drag=false,tmpos,cimg,imgcurr,lock=false;
var maxpos=0;
		
$(document).ready(function(){	
	//W = $(gallery).width(); 
	var regexp=/[^0-9]/g;
	W = $(gallery).css('width').replace(regexp,''); 
	l=0;
	var i;

	$(btnright).click(
	   function(){ 
	    if(lock) return true;
		$(btnleft).css('color','black');
		lock=true;
		var newpos=0,i;
		for(i=0;i<=imgcurr;i++) newpos+=img_width; //newpos+=imgs[i];
		//if(newpos>maxpos){ $(this).css('color','silver'); return ;}
		if(imgcurr>=imgs.length-1) { $(this).css('color','silver'); return false;}
		imgcurr++;
		$(container).animate( {left: '-'+newpos}, 200, function() { lock=false;  } );
	   }
	);

	$(btnleft).click(
	   function(){ 
		if(imgcurr<=0) { $(this).css('color','silver');return ;}
		$(btnright).css('color','black');
		lock=true;
		imgcurr--;
		var newpos=0,i;
		for(i=0;i<imgcurr;i++) newpos+=img_width; //newpos+=imgs[i];
		$(container).animate( {left: '-'+newpos}, 200, function() { lock=false;  } );
		if(imgcurr<=0) { $(this).css('color','silver');}
	   }
	);

	// clear container
	var imgs=[];
	$(container).children().each(function(){
	   if($(this).is('a'))
	     imgs[imgs.length]=$(this).clone();
	});
	//alert(imgs);
	$(container).empty();
	for(i=0;i<imgs.length;i++) $(container).append(imgs[i]);
	imgs=[];

	
	
    w = $(container).width(); 
	h = $(container).height(); 

	$(container+' a').each(function(){
	  imgs[imgs.length]=$(this).width();
	  $(this).click(function(event){
	    cimg=$(this).attr('href');
		$('#bigimg').fadeOut(1000,function(){
        $('#bigimg').empty();
		$('#bigimg').css('top',$(window).scrollTop());
		$('#bigimg').prepend("<img style='border:2px solid red;' src="+cimg+">");
		$('#bigimg').fadeIn(1000);
		});
		return false;
	  });
	});

	$(gallerybox).after("<br/><span id=bigimg style=''></span>");
	$('#bigimg').click(function(){ 	$(this).fadeOut(1000,function(){$('#bigimg').empty();});});
	$('#bigimg').fadeOut();
	$('#bigimg').css('height',$(window).height());
	imgcurr=0;
	$(btnleft).css('color','silver');

    $(container).draggable({ axis: 'x' });
	
	for(i=0;i<imgs.length;i++) maxpos+=imgs[i];
	maxpos=maxpos-W;
    //alert(maxpos+' '+W);
});	
*/