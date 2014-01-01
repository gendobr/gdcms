/*
Akkordeon menu
sample usage
    <style type="text/css">
        div#menu a{display:block;}
        .L0{margin-left:0px;}
        .L1{margin-left:10px;}
        .L2{margin-left:20px;}
        .L3{margin-left:30px;}
    </style>
    <script language="javascript" type="text/javascript">
        // configuration
        var menucontainer='menu';
        var levelClasses=['L0','L1','L2','L3'];
        var cookiePath='/cms';
        var cookieDomain='10.1.103.65';
    </script>

    <script language="javascript"
            type="text/javascript"
            src="../scripts/lib/jquery-1.4.2.min.js"></script>
    <script language="javascript"
            type="text/javascript"
            src="../scripts/lib/jquery-akkordeon-tree.js"></script>

    <div id="menu">
        <a href="#" class="L0">1</a>
        <a href="#" class="L1">1-1</a>
        <a href="#" class="L2">1-1-1</a>
        <a href="#" class="L2">1-1-2</a>
        <a href="#" class="L1">1-2</a>
        <a href="#" class="L2">1-2-1</a>
        <a href="#" class="L2">1-2-2</a>
        <a href="#" class="L0">2</a>
        <a href="#" class="L1">2-1</a>
        <a href="#" class="L2">2-1-1</a>
        <a href="#" class="L2">2-1-2</a>
        <a href="#" class="L2">2-1-3</a>
        <a href="#" class="L0">3</a>
        <a href="#" class="L1">3-1</a>
        <a href="#" class="L1">3-2</a>
        <a href="#" class="L1">3-3</a>
    </div>
*/




// temporary variables
var lvl=0;
var stateList={};


// get menu level
function getLevel(element){
	var cnt=levelClasses.length;
	var e=$(element);
	for(var i=0;i<cnt;i++){
		if(e.hasClass( levelClasses[i] )){
			return i;
		}
	}
	return 0;
}

function stateListJSON(){
   var delim='';
   str='';
   for(var i in stateList){
       str+=delim+'"'+i+'":"'+stateList[i]+'"';
       delim=',';
   }
   return '{'+str+'}';
}

function clk1(event){
	var menuitem=$(event.target);
	var menuitemId=menuitem.attr('id');
	var newstate=(stateList[menuitemId]=='fold'?'unfold':'fold');
	setState(menuitemId, newstate);
        setCookie( 'stateList', stateListJSON(), 1, cookiePath, cookieDomain, '' );
	event.stopPropagation();
}

// set visibility state
function setState(elementId, state){
	var element=$('#'+elementId);
	var elementLevel=getLevel(element);
	var parentLevel;

	// check state value
	newstate=(state=='fold'?'fold':'unfold');

	// save new state
	stateList[element.attr('id')]=newstate;

	if(newstate=='fold'){
		// fold all the descendants
		parentLevel=elementLevel;
		element.nextAll().each(function(index, descendantElement){
			var de=$(descendantElement);
			var descendantLevel=getLevel(de);
			if(parentLevel>=0 && parentLevel<descendantLevel){
			de.hide();
			stateList[de.attr('id')]='fold';
			}else{
			parentLevel=-1; // stop folding
			}
		});
	}else{
		parentLevel=elementLevel;
		element.nextAll().each(function(index, descendantElement){
			var de=$(descendantElement);
			var descendantLevel=getLevel(de);
			if(parentLevel>=0 && parentLevel<descendantLevel){ 
			if(parentLevel==descendantLevel-1){
				de.show();
			}else{
				de.hide();
			}
			stateList[de.attr('id')]='fold';
			}else{
			parentLevel=-1; // stop folding
			}
		});
	}
}

$(window).load(function() {
	var num=0;
	$('#'+menucontainer+' a').each(function(ind, elem){
		var newid='mi'+(++num);
		// set event handler
		$(elem).click(clk1);
	
		// set "id" attribute
		$(elem).attr('id',newid);
	
		stateList[newid]='unfold';      // save state unfold | fold 
	});
	var savedStateList=getCookie('stateList');
        if(savedStateList){
		try  {
			var stl=jQuery.parseJSON(savedStateList);
	 		var i;
			for(i in stateList){
				if(stl[i] && stateList[i]!=stl[i]){
					setState(i, stl[i]);
				}
			}
		}catch(err){
			for(i in stateList){setState(i, 'fold');}
		}
        }else{
		for(i in stateList){setState(i, 'fold');}
	}
});


function setCookie( name, value, expires, path, domain, secure ){
	// set time, it's in milliseconds
	var today = new Date();
	today.setTime( today.getTime() );

	/*
	if the expires variable is set, make the correct
	expires time, the current script below will set
	it for x number of days, to make it for hours,
	delete * 24, for minutes, delete * 60 * 24
	*/
	if ( expires ){
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );

	document.cookie = name + "=" +escape( value ) +
	( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) +
	( ( path ) ? ";path=" + path : "" ) +
	( ( domain ) ? ";domain=" + domain : "" ) +
	( ( secure ) ? ";secure" : "" );
}


function getCookie( check_name ) {
	// first we'll split this cookie up into name/value pairs
	// note: document.cookie only returns name=value, not the other components
	var a_all_cookies = document.cookie.split( ';' );
	var a_temp_cookie = '';
	var cookie_name = '';
	var cookie_value = '';
	var b_cookie_found = false; // set boolean t/f default f

	for ( i = 0; i < a_all_cookies.length; i++ ){
		// now we'll split apart each name=value pair
		a_temp_cookie = a_all_cookies[i].split( '=' );


		// and trim left/right whitespace while we're at it
		cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');

		// if the extracted name matches passed check_name
		if ( cookie_name == check_name ){
			b_cookie_found = true;
			// we need to handle case where cookie has no value but exists (no = sign, that is):
			if ( a_temp_cookie.length > 1 ){
				cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
			}
			// note that in cases where cookie is initialized but no value, null is returned
			return cookie_value;
			break;
		}
		a_temp_cookie = null;
		cookie_name = '';
	}
	if ( !b_cookie_found ){
		return null;
	}
}

