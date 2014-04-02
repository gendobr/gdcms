


$(document).ready(function(){
	var regex = {
		media: /@media[^\{]+\{([^\{\}]*\{[^\}\{]*\})+/gi,
		keyframes: /@(?:\-(?:o|moz|webkit)\-)?keyframes[^\{]+\{(?:[^\{\}]*\{[^\}\{]*\})+[^\}]*\}/gi,
		comments: /\/\*[^*]*\*+([^/][^*]*\*+)*\//gi,
		urls: /(url\()['"]?([^\/\)'"][^:\)'"]+)['"]?(\))/g,
		findStyles: /@media *([^\{]+)\{([\S\s]+?)$/,
		only: /(only\s+)?([a-zA-Z]+)\s?/,
		minw: /\(\s*min\-width\s*:\s*(\s*[0-9\.]+)(px|em)\s*\)/,
		maxw: /\(\s*max\-width\s*:\s*(\s*[0-9\.]+)(px|em)\s*\)/,
		minmaxwh: /\(\s*m(in|ax)\-(height|width)\s*:\s*(\s*[0-9\.]+)(px|em)\s*\)/gi,
		other: /\([^\)]*\)/g
	};
	
	isUnsupportedMediaQuery = function(query) {
		return query.replace(regex.minmaxwh, "").match(regex.other);
	};

	var cssFiles=[];
	
    var parseCss=function(cssFile){

        var styles=cssFile.src;
        var media=cssFile.media;
        var href=cssFile.href;
        
		var qs = styles.replace(regex.comments, "").replace(regex.keyframes, "").match(regex.media);
		var ql = qs && qs.length || 0;

		href = href.substring(0, href.lastIndexOf("/"));
		if (href.length) {
		  href += "/";
		}

		var repUrls = function(css) {
		  return css.replace(regex.urls, "$1" + href + "$2$3");
		};
        
        // media queries not found, use styles "as is"
		if(ql==0){
			return {
				src:cssFile.src,
				media:media,
				href:cssFile.href,
				style:styles
			}
		}

        var useMedia = !ql && media;
		if (useMedia) {
		  ql = 1;
		}
		// console.log(useMedia);
		var mediastyles=[];
		var rules = [];
		for (var i = 0; i < ql; i++) {
			var fullq, thisq, eachq, eql;
		    if (useMedia) {
			    fullq = media;
			    rules.push(repUrls(styles));
		    } else {
				fullq = qs[i].match(regex.findStyles) && RegExp.$1;
			    rules.push(RegExp.$2 && repUrls(RegExp.$2));
		    }
		    // console.log(fullq, rules);
 		    eachq = fullq.split(",");
			eql = eachq.length;
			for (var j = 0; j < eql; j++) {
				thisq = eachq[j];
				if (isUnsupportedMediaQuery(thisq)) {
				  continue;
				}
				mediastyles.push({
				  media: thisq.split("(")[0].match(regex.only) && RegExp.$2 || "all",
				  rules: rules.length - 1,
				  hasquery: thisq.indexOf("(") > -1,
				  minw: thisq.match(regex.minw) && parseFloat(RegExp.$1) + (RegExp.$2 || ""),
				  maxw: thisq.match(regex.maxw) && parseFloat(RegExp.$1) + (RegExp.$2 || "")
				});
			}
		}
		
		// console.log(mediastyles, rules);


        return {
			src:cssFile.src,
			media:(useMedia?media:false),
			href:cssFile.href,
			mediastyles:mediastyles,
			rules:rules,
			style:false
		}
	}


    var applyCss=function(){
		// check if all CSS 
	    for(var i=0, cnt=cssFiles.length;i<cnt; i++){
		    if(!cssFiles[i].src){
				return;
			}
	    }
	    // console.log('(re)applyCss');
	    $('.jmediaqueries').remove();
	    
	    
        //console.log(cssFiles);

		var currWidth = $(document).innerWidth();
		var styleBlocks = {};

	    for(var i=0, cnt=cssFiles.length;i<cnt; i++){
		    if(cssFiles[i].style){
	    	   // media queries not found, place styles AS IS
			   $( "<style type=\"text/css\" class=\"jmediaqueries\" "+( cssFiles[i].media ?" media=\""+cssFiles[i].media+"\"":'')+">"+cssFiles[i].style+"</style>" ).appendTo( "head" )
			}else{
	    	    // media queries found
	    	    // console.log(i, cssFiles[i], cssFiles[i].mediastyles);
	    	    for (var j=0, cnt1=cssFiles[i].mediastyles.length; j<cnt1; j++ ) {
						var thisstyle = cssFiles[i].mediastyles[j], 
							min = thisstyle.minw, 
							max = thisstyle.maxw, 
							minnull = min === null, 
							maxnull = max === null, 
							em = "em";
						if (!!min) {
						  min = parseFloat(min) * (min.indexOf(em) > -1 ? eminpx || getEmValue() : 1);
						}
						if (!!max) {
						  max = parseFloat(max) * (max.indexOf(em) > -1 ? eminpx || getEmValue() : 1);
						}
						if (!thisstyle.hasquery || (!minnull || !maxnull) && (minnull || currWidth >= min) && (maxnull || currWidth <= max)) {
						  if (!styleBlocks[thisstyle.media]) {
							styleBlocks[thisstyle.media] = [];
						  }
						  styleBlocks[thisstyle.media].push(cssFiles[i].rules[thisstyle.rules]);
						}
			    }
			}
	    }
		for (var k in styleBlocks) {
		  if (styleBlocks.hasOwnProperty(k)) {
			  $( "<style type=\"text/css\" class=\"jmediaqueries\" media=\""+k+"\">"+styleBlocks[k].join("\n")+"</style>" ).appendTo( "head" );
		  }
		}
	}

	
	var links=$('link[rel="stylesheet"]');
	links.each(function(i,e){
		var el=$(e);
		var obj={'href':el.attr('href'),'media':el.attr('media'), 'src':'','style':''};
		cssFiles.push(obj);
	});

    // CSS file load listener factory
    var onCssLoaded=function(i){
		return function(data){
			// console.log(cssFiles[i].href+' loaded');
			cssFiles[i].src=data;
			cssFiles[i]=parseCss(cssFiles[i]);
			applyCss();
		}
	}
	// initiale load of each CSS file
	for(var i=0, cnt=cssFiles.length;i<cnt; i++){
		$.get( cssFiles[i].href, onCssLoaded(i));
	}
	
	// set applyCss() as "resize" event listener
	$( window ).resize(applyCss);
});

