/*
   author: Gennadiy Dobrovolsky
   date:   September 27, 2006
*/

var month_names=["January","February","March","April","May","Juny","July","August","September","October","November","December"];

function Calendar(block,element,name)
{
  this.block   = document.getElementById(block);
  this.element = document.getElementById(element);
  this.name = name;
  this.get_calendar=get_calendar;
  this.draw_calendar=draw_calendar;
  this.set_value=set_value;
  
  if(this.block && this.block.innerHTML) this.header=this.block.innerHTML;
  else this.header='';

  var now=new Date();
  this.month_today = now.getMonth();
  this.year_today  = now.getYear();
  if(this.year_today<1000) this.year_today+=1900;
}

function get_calendar(month,year)
{
  var n_days=new Date(year,month-1,32); 
  n_days=32-n_days.getDate(); // ïîêàçóº ê³ëüê³ñòü äí³â ó ì³ñÿö³
  
  var fst=new Date(year,month-1,1);
  
  //var cnt=fst.getDay()-1; if(cnt<0) cnt=6;
  var cnt=fst.getDay();
  var i,j;
  var tor='';

  tor=tor+'<tr>';
  for(j=0;j<cnt;j++) tor=tor+'<td></td>';
  
  for(i=1;i<=n_days;i++)
  {
    if(j%7==0) { tor=tor+'</tr><tr>'; j=0;}
    tor=tor+'<td><a href=# onclick="'+this.name+'.set_value(\''+year+'-'+(month<10?'0':'')+month+'-'+(i<10?'0':'')+i+'\')">'+i+'</a></td>';
    j++;
  }
  for(i=7-j;i>0;i--)  tor=tor+'<td></td>';
  tor=tor+'</tr>';

  var next_year  = year;
  var next_month = month+1;
  if(next_month==13) {next_month=1;next_year++;}
  
  var prev_year  = year;
  var prev_month = month-1;
  if(prev_month==0) {prev_month=12;prev_year--;}

  _tor='<table border=0><tr><td colspan=4><nobr><a href="#" onclick="';
  _tor=_tor+this.name+'.draw_calendar('+prev_month+','+prev_year;
  _tor=_tor+');return false;">&lt;</a> '+month_names[month-1];
  _tor=_tor+' <a href="#" onclick="'+this.name;
  _tor=_tor+'.draw_calendar('+next_month+','+next_year;
  _tor=_tor+');return false;">&gt;</a></nobr></td>';
  _tor=_tor+'<td colspan=3 align=right><nobr><a href="#" onclick="';
  _tor=_tor+this.name+'.draw_calendar('+month+','+(year-1);
  _tor=_tor+');return false;">&lt;</a> '+year;
  _tor=_tor+' <a href="#" onclick="'+this.name;
  _tor=_tor+'.draw_calendar('+month+','+(year+1);
  _tor=_tor+');return false;">&gt;</a></nobr></td></tr>';
  _tor=_tor+'<tr><th>Âñ</th><th>Ïí</th><th>Âò</th><th>Ñð</th><th>×ò</th><th>Ïò</th>';
  _tor=_tor+'<th>Ñá</th></tr>'+tor+'</table>';
  //alert(tor);
  return _tor;
}

function draw_calendar(month,year)
{
   if(this.block && this.block.innerHTML)
   this.block.innerHTML=this.header+this.get_calendar(month,year);
}
function set_value(str)
{
   if(this.element)
   {
      if(typeof(this.element.value)=='string')
      {
        this.element.value=str+' 00:00:00';
      }
   }
}
//alert(typeof(draw_calendar));

function cs(cid)
{
    var lay=document.getElementById(cid);
    var btn=document.getElementById(cid+'_open');
    if (lay.style.display=="none")
    {
       lay.style.display="inline-block";
       if(btn) btn.innerHTML=' &Lambda; ';
    }
    else
    {
       lay.style.display="none";
       if(btn) btn.innerHTML=' V ';
    }
}


function attach_calendar_to(field_id, varname)
{
   var st=''+Math.random();
   var uid='elem'+st.replace(/\./,'');
   varname=uid;
   window[varname]='';
   document.write('<a id='+uid+'_open href="javascript:void(cs(\''+uid+'\'))" style="display:inline-block;padding:4pt;background-color:orange;"> V </a>');
   document.write('<span id='+uid+' style="display:none;background-color:#e0e0e0;padding:10px;position:absolute;">&nbsp;</span>');
   eval(''+varname+'=new Calendar(uid,field_id,"'+varname+'");')
   eval(''+varname+'.draw_calendar('+varname+'.month_today, '+varname+'.year_today);');
}
/*
Sample use:
      <input type=text
             name="filter_date_created_min"
             id="filter_date_created_min"
             value="">
      <a id=calendar1_open class=menu_btn href=# onclick="cs('calendar1'); return false;">[+]</a>
      <div id=calendar1 class=menu_block style='display:none;'>&nbsp;</div>
      <script type="text/javascript">
      <!--
      c1=new Calendar('calendar1','filter_date_created_min','c1');
      c1.draw_calendar(c1.month_today, c1.year_today);
      // -->
      </script>

*/