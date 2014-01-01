//var message_area;


// check URL syntax
function is_valid_url(str)
{
  return str.match(/^(https?|mms|ftp):\/\/([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(\/[-.a-z0-9_~&]+)*\/?(\?.*)?$/i);
//return str.match(/^(https?|mms|ftp):\/\/([a-z0-9_-]+\.)+([a-z0-9_-]+)(\/[.a-z0-9_0]+)*$/i);
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



   function url(textarea_id){
       var innerhtml;
       innerhtml=get_selected_text();
       if(is_valid_url(innerhtml)) {
           operate("<a href=\""+innerhtml+"\">","</a>",''+textarea_id);
       }
       else
       {
           operate("<a href=\"\">","</a>",''+textarea_id);
       }
   }

   function editor_init(textarea_id)
   {
       var buttons=[
            {'className':'editbutton','value':'Заголовок 1' ,'style':'width:auto;height:auto;font-weight:bold;font-size:160%;', 'onclick':'operate("<h1>","</h1>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Заголовок 2' ,'style':'width:auto;height:auto;font-weight:bold;font-size:140%;', 'onclick':'operate("<h2>","</h2>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Заголовок 3' ,'style':'width:auto;height:auto;font-weight:bold;font-size:120%;', 'onclick':'operate("<h3>","</h3>","'+textarea_id+'");'}
           ,{'div':'1'}
           ,{'className':'editbutton','value':'Нумерований список' ,'style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'operate("<ol>","</ol>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Список з маркерами' ,'style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'operate("<ul>","</ul>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Пункт списку' ,'style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'operate("<li>","</li>","'+textarea_id+'");'}
           ,{'div':'1'}
           ,{'className':'editbutton','value':'Абзац'  ,'style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'operate("<p>","</p>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Жирний шрифт'  ,'style':'width:auto;height:auto;font-size:100%;font-weight:bold;'                , 'onclick':'operate("<b>","</b>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Курсив'  ,'style':'width:auto;height:auto;font-size:100%;font-style:italic; '                , 'onclick':'operate("<i>","</i>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Підкреслення'  ,'style':'width:auto;height:auto;font-size:100%;text-decoration:underline;'                , 'onclick':'operate("<u>","</u>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Розрив рядка' ,'style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'operate("","<br/>","'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Адреса Інтернету','style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'url("'+textarea_id+'");'}
           ,{'className':'editbutton','value':'Зображення','style':'width:auto;height:auto;font-size:100%;'                , 'onclick':'operate("<img src=\\"\\" alt=\\"\\" align=\\"\\">","","'+textarea_id+'");'}
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
           if(buttons[i]['div']){
               btn=document.createElement("div");
           } else{
               btn=document.createElement("input");
               btn.setAttribute("class",buttons[i]['className']);
               btn.setAttribute("type","button");
               btn.setAttribute("value",buttons[i]['value']);
               btn.setAttribute("onclick",buttons[i]['onclick']);
               btn.setAttribute("style",buttons[i]['style']);
           }

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
       for(i=0;i<cnt;i++)
       {
           if(buttons[i]['div']){
               btn=document.createElement("div");
           } else{
               btn=document.createElement("input");
               btn.setAttribute("class",buttons[i]['className']);
               btn.setAttribute("type","button");
               btn.setAttribute("value",buttons[i]['value']);
               eval('btn.onclick=function(){'+buttons[i]['onclick']+'}'); // !!!!!!!
               //btn.setAttribute("style",buttons[i]['style']);
               btn.style.cssText = buttons[i]['style'];
           }
           toolbar.appendChild(btn);
       }
       var oParent = txt.parentNode;
       oParent.insertBefore(toolbar,txt);
       //try{toolbar.innerHTML=toolbar.innerHTML;} catch(e){}
       //oParent.appendChild(toolbar);
   }