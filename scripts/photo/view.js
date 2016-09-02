var scroller=function(containerId, imgPerPage){


    this.containerId=containerId;
    this.container=$('#'+this.containerId);

    var slide=$('#'+this.containerId+' a').first()
    this.oneSlideWidth=parseInt(slide.outerWidth()) + parseInt(slide.css('margin-left')) + parseInt(slide.css('margin-right'));

    // console.log('slide.outerWidth()',slide,slide.outerWidth(), slide.css('margin-left'), slide.css('margin-right'));

    this.nImages = $('#'+this.containerId+' a').length;

    // console.log('this.nImages',this.nImages);


    var parentWidth=this.container.parent().innerWidth();
    // this.imgPerPage= imgPerPage;
    this.imgPerPage= Math.floor(parentWidth/this.oneSlideWidth);
    if(this.imgPerPage<1) this.imgPerPage=1;
    // console.log("this.imgPerPage",this.imgPerPage);

    this.nPages=Math.ceil(this.nImages/this.imgPerPage);

    this.currentPage=0;

    var ff=function(self,i){
        return function(){

            var newMargin=-( (i-1) * self.oneSlideWidth * self.imgPerPage)+'px';
            // console.log('newMargin',newMargin, self.oneSlideWidth, self.imgPerPage);
            self.pageNav.empty().append(self.getPageNavDom(i-1));
            self.container.css({'margin-left':newMargin})
        }
    }

    this.getPageNavDom=function(currentPosition){

        this.currentPage=currentPosition%this.nPages;

        var dom=$('<div></div>');


        if(this.currentPage>0){
            var a=$('<a href="javascript:void('+this.currentPage+')" class="pagenav prev"><i class="fa fa-chevron-left fa-1x" aria-hidden="true"></i></a>');
            a.click(ff(this,this.currentPage));
            dom.append(a);
        }

        var iMin=this.currentPage-4;
        if(iMin<=0){
            iMin=0;
        }else{
            var a=$('<a class="pagenav first" href="javascript:void('+1+')">1</a>');
            a.click(ff(this,1));
            dom.append(a);
            dom.append($('<span>...</span>'));
        }

        var iMax=this.currentPage+4;
        if(iMax>=this.nPages){
            iMax=this.nPages-1;
        }

        var a;
        for(var i=iMin; i<=iMax; i++){
            if(i==this.currentPage){
                a=$('<a class="pagenav active" href="javascript:void('+i+')">'+(i+1)+'</a>');
            }else{
                a=$('<a class=pagenav href="javascript:void('+i+')">'+(i+1)+'</a>');
            }
            a.click(ff(this,i+1));
            dom.append(a);
        }


        if(iMax<(this.nPages-1)){
            var a=$('<a class="pagenav last" href="javascript:void('+this.nPages+')">'+this.nPages+'</a>');
            a.click(ff(this,this.nPages));
            dom.append($('<span>...</span>'));
            dom.append(a);
        }


        if(this.currentPage<(this.nPages-1)){
            var a=$('<a href="javascript:void('+(this.currentPage+2)+')" class="pagenav next"><i class="fa fa-chevron-right fa-1x" aria-hidden="true"></i></a>');
            a.click(ff(this,this.currentPage+2));
            dom.append(a);
        }

        return dom;

    }

    this.drawPageNav=function(pageNavContainer){
        this.pageNav = $(pageNavContainer);
        this.pageNav.append(this.getPageNavDom(0));
    }

}


