function decrypt(key,str)
{
  var i, ch0, ch1, pos0, pos1, n_from, n_to;
  var tor=str;
  var echo='';
  var n_chars = str.length;
  var n_pairs = Math.floor( 0.6 * n_chars);
  //n_pairs=3;
  var from=hash(key);
  //alert('hash('+key+')='+hash(key))
  for(i=0; i<n_pairs; i++)
  {
     from   = get_next_rnd(from);
     from   = get_next_rnd(from);
     to     = get_next_rnd(from);

     n_from = Math.floor(from * n_chars);
     n_to   = Math.floor(to * n_chars);
     //alert(n_from + '=>' + n_to);
     if(n_from!=n_to)
     {
       if(from < to)
       {
         pos0 = n_from;
         pos1 = n_to;
       }
       else
       {
         pos1 = n_from;
         pos0 = n_to;
       }
       ch0  = tor.charAt(pos0);
       ch1  = tor.charAt(pos1);
       
       //echo=echo+"\n"+'before:'+tor;
       //echo=echo+"\n"+n_from + '<=>' + n_to;
       //echo=echo+"\n"+tor.substring(0,pos0)+'*'+ch1+'*'+tor.substring(pos0+1,pos1)+'*'+ch0+'*'+tor.substring(pos1+1);
       tor= tor.substring(0,pos0)+ch1+tor.substring(pos0+1,pos1)+ch0+tor.substring(pos1+1);
       //echo=echo+"\n"+'after :'+tor;
     }
  }
  //alert(echo);
  return tor;
}


function hash(key)
{
  if(typeof(key)!='string') return 0;
  var keylen  = key.length;
  var hash   =0;
  var i;
  for(i=0;i<keylen;i++)
  {
     hash = ( hash + key.charCodeAt(i) )/255;
  }
  return Math.floor(100000000 * hash+0.5)/100000000;
}

function get_next_rnd(num)
{
  var tor  = 11 * num + 3.14159269;
  tor -= Math.floor(tor);
  //prn($num.'=>'.$tor);
  return Math.floor(100000000 * tor+0.5)/100000000;
}




function Get_Cookie(name)
{
    var start = document.cookie.indexOf(name+"=");
    var len = start+name.length+1;
    if ((!start) && (name != document.cookie.substring(0,name.length))) return null;
    if (start == -1) return null;
    var end = document.cookie.indexOf(";",len);
    if (end == -1) end = document.cookie.length;
    return unescape(document.cookie.substring(len,end));
}

function SetCookie (name, value)
{
  var argv = SetCookie.arguments;
  var argc = SetCookie.arguments.length;
  var expires = (argc > 2) ? argv[2] : null;
  var path = (argc > 3) ? argv[3] : null;
  var domain = (argc > 4) ? argv[4] : null;
  var secure = (argc > 5) ? argv[5] : false;
  document.cookie = name + "=" + escape (value) +
    ((expires == null) ? "" : ("; expires=" + expires.toGMTString())) +
    ((path == null) ? "" : ("; path=" + path)) +
    ((domain == null) ? "" : ("; domain=" + domain)) +
    ((secure == true) ? "; secure" : "");
}

function codes_to_str(strcodes)
{
  var arr= strcodes.split(':');
  var cnt=arr.length;
  for(var i=0; i<cnt; i++)
  {
    arr[i]=dict[arr[i]];
  }
  return arr.join('');
}

