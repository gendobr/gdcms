
function insert_html(code, text_field_id)
{
  cc=window.opener;
  if(cc)
  {
    //var tmp=cc.document.getElementById('_'+text_field_id);
    //alert('_'+text_field_id+'=='+tmp);
    //cc.editor_insertHTML('$obj_name', '<img src=\"' + image_url + '\">');
    //alert(typeof(cc.editor_insertHTML));

    var area = cc.document.getElementById(text_field_id);
    if(area)
    {
      if(typeof(cc.editor_insertHTML)=='undefined')
      {
        area.value = area.value + "\n" + code;
      }
      else
      {
        cc.editor_insertHTML(text_field_id, code);
      }
      window.focus();
      alert('OK!');
    }
  }
}

function insert_img_html(URL, text_field_id)
{
  insert_html('<img src="'+URL+'" align="left" style="margin-right:10pt;margin-bottom:10pt;">', text_field_id);
}

function insert_link_html(URL, link_text, text_field_id)
{
  insert_html('<a href="' + URL + '">' + link_text + '</a>', text_field_id);
}
