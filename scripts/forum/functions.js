var rus_lr2 = ('Е-е-О-о-Ё-Ё-Ё-Ё-Ж-Ж-Ч-Ч-Ш-Ш-Щ-Щ-Ъ-Ь-Э-Э-Ю-Ю-Я-Я-Я-Я-ё-ё-ж-ч-ш-щ-э-ю-я-я').split('-');
var lat_lr2 = ('/E-/e-/O-/o-ЫO-Ыo-ЙO-Йo-ЗH-Зh-ЦH-Цh-СH-Сh-ШH-Шh-ъ'+String.fromCharCode(35)+'-ь'+String.fromCharCode(39)+'-ЙE-Йe-ЙU-Йu-ЙA-Йa-ЫA-Ыa-ыo-йo-зh-цh-сh-шh-йe-йu-йa-ыa').split('-');
var rus_lr1 = ('А-Б-В-Г-Д-Е-З-И-Й-К-Л-М-Н-О-П-Р-С-Т-У-Ф-Х-Х-Ц-Щ-Ы-Я-а-б-в-г-д-е-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-х-ц-щ-ъ-ы-ь-ь-я').split('-');
var lat_lr1 = ('A-B-V-G-D-E-Z-I-J-K-L-M-N-O-P-R-S-T-U-F-H-X-C-W-Y-Q-a-b-v-g-d-e-z-i-j-k-l-m-n-o-p-r-s-t-u-f-h-x-c-w-'+String.fromCharCode(35)+'-y-'+String.fromCharCode(39)+'-'+String.fromCharCode(96)+'-q').split('-');
var rus_rl = ('А-Б-В-Г-Д-Е-Ё-Ж-З-И-Й-К-Л-М-Н-О-П-Р-С-Т-У-Ф-Х-Ц-Ч-Ш-Щ-Ъ-Ы-Ь-Э-Ю-Я-а-б-в-г-д-е-ё-ж-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-ц-ч-ш-щ-ъ-ы-ь-э-ю-я').split('-');
var lat_rl = ('A-B-V-G-D-E-JO-ZH-Z-I-J-K-L-M-N-O-P-R-S-T-U-F-H-C-CH-SH-SHH-'+String.fromCharCode(35)+String.fromCharCode(35)+'-Y-'+String.fromCharCode(39)+String.fromCharCode(39)+'-JE-JU-JA-a-b-v-g-d-e-jo-zh-z-i-j-k-l-m-n-o-p-r-s-t-u-f-h-c-ch-sh-shh-'+String.fromCharCode(35)+'-y-'+String.fromCharCode(39)+'-je-ju-ja').split('-');


function translatesymboltocyrillic(pretxt,txt)
{
	var doubletxt = pretxt+txt;
	var code = txt.charCodeAt(0);
	if (!(((code>=65) && (code<=123))||(code==35)||(code==39))) return doubletxt;
	var ii;
	for (ii=0; ii<lat_lr2.length; ii++)
	{
		if (lat_lr2[ii]==doubletxt) return rus_lr2[ii];
	}
	for (ii=0; ii<lat_lr1.length; ii++)
	{
		if (lat_lr1[ii]==txt) return pretxt+rus_lr1[ii];
	}
	return doubletxt;
}


function translatesymboltolatin(symb)
{
	var ii;
	for (ii=0; ii<rus_rl.length; ii++)
	{
		if (rus_rl[ii]==symb)
		return lat_rl[ii];
	}
	return symb;
}


function translateAlltoCyrillic()
{
  txt = message_area.value;
  var txtnew = translatesymboltocyrillic("",txt.substr(0,1));
  var symb = "";

  var do_translate=(txt.substr(0,1)!='[');
  for (kk=1;kk<txt.length;kk++)
  {
    if(do_translate)
    {
      symb = translatesymboltocyrillic(txtnew.substr(txtnew.length-1,1),txt.substr(kk,1));
      txtnew = txtnew.substr(0,txtnew.length-1) + symb;
      if(txt.substr(kk,1)=='[') do_translate=false;
    }
    else
    {
      txtnew = txtnew + txt.substr(kk,1);
      if(txt.substr(kk,1)==']') do_translate=true;
    }
  }
  message_area.value = txtnew;
  message_area.focus();
  return;
}



function translateAlltoLatin()
{
  var txt = message_area.value;
  txtnew="";
  var symb = "";
  for (kk=0;kk<txt.length;kk++)
  {
    symb = txt.substr(kk,1);
    symb = translatesymboltolatin(symb);
    txtnew = txtnew.substr(0,txtnew.length) + symb;
  }
  message_area.value = txtnew;
  message_area.focus();
  return;
}















// =============================================================================

// insert selected text into textarea
    function quoteSelection(message_area)
    {
      theSelection=get_selected_text();
      if (theSelection)
      {
      // Add tags around selection
         insert_text( '[quote]\n' + theSelection + '\n[/quote]\n');
         document.getElementById(message_area).focus();
         text = '';
         return;
      }
      else
      {
         alert('Выделите любой текст на странице и нажмите эту кнопку');
      }
    }



// store caret position
   function storeCaret(message_area)
   {
      textEl=document.getElementById(message_area)
      if(textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
   }


// insert emoticon at cursor position, IE and Opera
   function insert_text(text,message_area)
   {
     var m_area=document.getElementById(message_area);
     if (m_area.createTextRange && m_area.caretPos)
     {
        var caretPos = m_area.caretPos;
        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
        m_area.focus();
     }
     else
     {
        m_area.value  += text;
        m_area.focus();
     }
   }


// insert tags
   function AddSelTextIE(bbopen, bbclose,message_area)
   {
        var m_area=document.getElementById(message_area);
  	if(m_area.caretPos) m_area.caretPos.text = bbopen + m_area.caretPos.text + bbclose;
  	else m_area.value += bbopen + bbclose;
  	m_area.focus()
   }
   function AddSelTextStandard(bbopen, bbclose,message_area)
   {
       var m_area=document.getElementById(message_area);
       //alert(m_area.value.substring(m_area.selectionStart, m_area.selectionEnd));
       var oldstr = m_area.value;
       var newstr = oldstr.substring(0,m_area.selectionStart);
       newstr+= bbopen;
       newstr+= oldstr.substring(m_area.selectionStart, m_area.selectionEnd);
       newstr+= bbclose;
       newstr+= oldstr.substring(m_area.selectionEnd,oldstr.length);
       //alert(m_area.selectionStart+' '+m_area.selectionEnd+' '+oldstr.length+ ' ' +oldstr.substring(m_area.selectionEnd,oldstr.length));

       var newstart  = m_area.selectionStart + bbopen.length;
       var newend    = m_area.selectionEnd   + bbopen.length;
       m_area.value=newstr;
       m_area.selectionStart = newstart;
       m_area.selectionEnd   = newend;
   }
   var operate=AddSelTextStandard;


   function editor_init(textarea_id)
   {
       
       var buttons=[
            {'className':'editbutton','value':'B'     ,'style':'font-weight:bold;'          , 'onclick':'operate("[b]","[/b]","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'i'     ,'style':'font-style:italic;'         , 'onclick':'operate("[i]","[/i]","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'u'     ,'style':'text-decoration: underline;', 'onclick':'operate("[u]","[/u]","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Quote' ,'style':'width:auto;height:auto;'    , 'onclick':'operate("[quote]"+get_selected_text(),"[/quote]","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Code'  ,'style':'width:auto;height:auto;'    , 'onclick':'operate("[code]","[/code]","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'img'   ,'style':'width:auto;height:auto;'    , 'onclick':'operate("[img]","[/img]","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'URL'   ,'style':'width:auto;height:auto;'    , 'onclick':'operate("[url]","[/url]","'+textarea_id+'");'}

           ,{'className':'smilesbtn','value':' ','style':'border:none;width:1px;height:1px;display:block;','onclick':'void();'}

           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_biggrin.gif);','onclick':'operate(":D","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_smile.gif);', 'onclick':'operate(":)","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_sad.gif);','onclick':'operate(":(","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_eek.gif);','onclick':'operate(":shock:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_confused.gif);','onclick':'operate(":?","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_cool.gif);','onclick':'operate("8)","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_surprised.gif);',  'onclick':'operate(":o","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_lol.gif);',  'onclick':'operate(":lol:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_mad.gif);',  'onclick':'operate(":x","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_razz.gif);',  'onclick':'operate(":P","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_redface.gif);',  'onclick':'operate(":red:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_cry.gif);',  'onclick':'operate(":cry:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_evil.gif);',  'onclick':'operate(":evil:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_twisted.gif);',  'onclick':'operate(":twisted:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_rolleyes.gif);',  'onclick':'operate(":roll:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_wink.gif);',  'onclick':'operate(":wink:","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_neutral.gif);',  'onclick':'operate(":|","","'+textarea_id+'");'}
           ,{'className':'smilesbtn','value':' ','style':'background-image:url('+site_root_url+'/img/smiles/icon_mrgreen.gif);',  'onclick':'operate(":mrgreen:","","'+textarea_id+'");'}
       ];


       document.body.setAttribute("class", "attribute-test");
       if (document.body.className == "attribute-test")
       {
          // Атрибуты работают корректно (не Internet Explorer или будущая исправленная версия)
             operate=AddSelTextStandard;
             editor_init_standard(textarea_id,buttons);
       }
       else
       {
          // Использовать атрибуты нельзя (Internet Explorer)
             operate=AddSelTextIE;
             editor_init_IE(textarea_id,buttons);
       }


   }

   function  editor_init_standard(textarea_id,buttons)
   {
       var txt=document.getElementById(textarea_id);
       var toolbar = document.createElement("div");
       var cnt=buttons.length;
       var i,btn,j;
       for(i=0;i<cnt;i++)
       {
           btn=document.createElement("input");
           btn.setAttribute("class",buttons[i]['className']);
           btn.setAttribute("type","button");
           btn.setAttribute("value",buttons[i]['value']);
           btn.setAttribute("onclick",buttons[i]['onclick']);
           btn.setAttribute("style",buttons[i]['style']);

           toolbar.appendChild(btn);
       }

       var oParent = txt.parentNode;
       oParent.insertBefore(toolbar,txt);
   }

   function  editor_init_IE(textarea_id,buttons)
   {
       var txt=document.getElementById(textarea_id);

       eval("txt.onselect=function(){storeCaret(\""+textarea_id+"\");}");        // !!!!!!!
       eval("txt.onclick =function(){storeCaret(\""+textarea_id+"\");}");        // !!!!!!!
       eval("txt.onkeyup =function(){storeCaret(\""+textarea_id+"\");}");        // !!!!!!!

       var toolbar = document.createElement("div");
       var cnt=buttons.length;
       var i,btn,j;
       for(i=0;i<cnt;i++){
           btn=document.createElement("input");
           btn.setAttribute("class",buttons[i]['className']);
           btn.setAttribute("type","button");
           btn.setAttribute("value",buttons[i]['value']);
           eval('btn.onclick=function(){'+buttons[i]['onclick']+'}'); // !!!!!!!
           //btn.setAttribute("style",buttons[i]['style']);
           btn.style.cssText = buttons[i]['style'];
           toolbar.appendChild(btn);
       }
       var oParent = txt.parentNode;
       oParent.insertBefore(toolbar,txt);
   }


   // get seleted text
   function get_selected_text()
   {
      var selected_text;
      selected_text = false;
      selected_text = (document.all) ? document.selection.createRange().text : document.getSelection();
      if(!selected_text)
      if(typeof(document.getSelection)=='function')
      {
         selected_text = document.getSelection();
      }
      return selected_text;
   }




var div_message='',request;

function editform(msg_id){
          var dv=document.getElementById('text'+msg_id);

          if(div_message!='') return false;

          document.body.setAttribute("class", "attribute-test");
          var iefix=!(document.body.className == "attribute-test");

	  // сохраняем текст сообщения в переменную div_message
	  div_message = dv.innerHTML;


	  // выводим форму изменения сообщения
          var ar=document.createElement("textarea");
              ar.setAttribute("rows","10");
              ar.setAttribute("cols","45");
              ar.setAttribute("name","msg_text");
              ar.setAttribute("id","msg_text_"+msg_id);

              var fu;
              eval('fu=function (){if (request.readyState == 4) { document.getElementById("msg_text_"+'+msg_id+').value=request.responseText; } }');
              request=ajax_load(site_root_url+'/index.php?action=forum/msg&msg_id='+msg_id,'',fu);

              if(iefix){ar.style.cssText = "display:block;width:100%;";}
              else{ar.setAttribute("style","display:block;width:100%;");}

          var mi=document.createElement("input");
              mi.setAttribute("type","hidden");
              mi.setAttribute("name","msg_id");
              mi.setAttribute("value",msg_id);

          var ok=document.createElement("input");
              ok.setAttribute("type","submit");
              ok.setAttribute("value","OK");

          var ca=document.createElement("input");
              ca.setAttribute("type","reset");
              ca.setAttribute("value","Cancel");
              if(iefix){eval('ca.onclick=function(){undo('+msg_id+');}');}
              else{ca.setAttribute("onclick",'undo('+msg_id+');');}

	  // выводим форму изменения сообщения
          var fr=document.createElement("form");
              fr.setAttribute("action",window.location.href);
              fr.setAttribute("method","POST");

          fr.appendChild(ar);
          fr.appendChild(mi);
          fr.appendChild(ok);
          fr.appendChild(ca);

          
          while (dv.firstChild) {dv.removeChild(dv.firstChild);}
          dv.appendChild(fr);


          editor_init("msg_text_"+msg_id);
}

function undo(msg_id){
  var dv=document.getElementById('text'+msg_id);
  while (dv.firstChild) {dv.removeChild(dv.firstChild);}
  dv.innerHTML= div_message;
  div_message='';
}