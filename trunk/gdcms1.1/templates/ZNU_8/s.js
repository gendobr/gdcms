function set_template(name)
{
  set_cookie ('template', name);
  window.location.reload();
}

function load_user_settings(){  var animate=get_cookie('animate');  if(animate && animate=='no') hide_animation();}

function hide_animation()
{
  var anim_logo='';
  var amin_marquee=document.getElementById('site_header_marquee');
  if(amin_marquee) amin_marquee.style.display='none';
  var anim_logo=document.getElementById('anim_logo');
  if(anim_logo) anim_logo.style.display='none';
  set_cookie ('animate', 'no');
}

function show_animation()
{
  var anim_logo='';
  var amin_marquee=document.getElementById('site_header_marquee');
  if(amin_marquee) amin_marquee.style.display='block';
  var anim_logo=document.getElementById('anim_logo');
  if(anim_logo) anim_logo.style.display='block';
  set_cookie ('animate', 'yes');
}

var attempts=0;
function show_drop_down_menu()
{
  var bla=document.getElementById('bla');
  if(bla) bla.style.display='block';
  attempts=1;
}
function hide_drop_down_menu()
{
  if(attempts>0) attempts--;
  else
  {
    var bla=document.getElementById('bla');
    if(bla) bla.style.display='none';
  }

}

// --------------------- function to manage cookies - begin --------------------
function get_cookie(name)
{
    var start = document.cookie.indexOf(name+"=");
    var len = start+name.length+1;
    if ((!start) && (name != document.cookie.substring(0,name.length))) return null;
    if (start == -1) return null;
    var end = document.cookie.indexOf(";",len);
    if (end == -1) end = document.cookie.length;
    return unescape(document.cookie.substring(len,end));
}

function set_cookie (name, value)
{
  var argv = set_cookie.arguments;
  var argc = set_cookie.arguments.length;
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
// --------------------- function to manage cookies - end ----------------------

