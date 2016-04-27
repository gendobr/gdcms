<?php
/*
  List of news for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

$debug=false;
run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    $input_vars['page_title']   =
    $input_vars['page_header']  =
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0) {
    $input_vars['page_title']  =
    $input_vars['page_header'] =
    $input_vars['page_content']= $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------


//------------------- check poll_id - begin ------------------------------------
if(isset($input_vars['poll_id'])) {
    $poll_id=(int)$input_vars['poll_id'];
    $this_poll_info=\e::db_getonerow("SELECT * FROM {$table_prefix}golos_pynannja WHERE id=$poll_id AND site_id={$site_id}");
    if($this_poll_info) {
        $poll_id=(int)$this_poll_info['id'];
        $this_poll_info['vidpovidi']=\e::db_getrows("SELECT * FROM {$table_prefix}golos_vidpovidi WHERE pynannja_id=$poll_id");
    }else $poll_id=0;
}
else {
    $poll_id=0;
}
// set default value
if(!isset($this_poll_info) || !$this_poll_info) $this_poll_info=Array('id'=>0,'title'=>'-','is_active'=>0,'poll_type'=>'radio','site_id'=>$site_id,'vidpovidi'=>Array());

//------------------- check poll_id - end --------------------------------------


//------------------- save data - begin ----------------------------------------
$messages='';
//prn($input_vars);
if(isset($input_vars['poll_save'])) {
    //prn($input_vars['poll']);
    if(strlen($poll_title=trim($input_vars['poll']['title']))==0) $messages.="<font color=red>{$text['Poll_type_in_question']}</font>";
    $is_active=isset($input_vars['poll']['is_active'])?1:0;
    $poll_type=( isset($input_vars['poll']['poll_type']) && $input_vars['poll']['poll_type']=='checkbox')?'checkbox':'radio';

    if(strlen($messages)==0) {
        # ----------------- save question - begin ------------------------------
        if($poll_id==0) {
            $query="INSERT INTO {$table_prefix}golos_pynannja(site_id,is_active,title,poll_type)
  			        VALUES($site_id,$is_active,'".mysql_escape_string($poll_title)."','{$poll_type}')";
            \e::db_execute($query);
            $poll_id=\e::db_getonerow("SELECT LAST_INSERT_ID() AS poll_id");
            $poll_id=$poll_id['poll_id'];
        }
        else {
            $query="UPDATE {$table_prefix}golos_pynannja
  			        SET title='".mysql_escape_string($poll_title)."'
  			           ,is_active=$is_active
  			           ,poll_type='{$poll_type}'
  			        WHERE id={$poll_id} AND site_id={$site_id}";
            \e::db_execute($query);
        }
        # ----------------- save question - end --------------------------------
        # prn($query);


        # ----------------- update variants - begin ----------------------------
        $to_add=Array();
        $to_delete=Array();
        $to_update=Array();
        if(!isset($input_vars['poll']['vidpovidi'])) $input_vars['poll']['vidpovidi']=Array();
        foreach($input_vars['poll']['vidpovidi'] as $key=>$val) {
            $val=trim($val['html']);
            //prn($key,$val);
            if($key<=0) {
                if(strlen($val)>0) $to_add[$key]=$val;
            }
            else {
                if(strlen($val)==0) $to_delete[$key]='';
                else $to_update[$key]=$val;
            }
        }

        foreach($this_poll_info['vidpovidi'] as $val) {
            if(!isset($input_vars['poll']['vidpovidi'][$val['id']])) {
                $to_delete[$val['id']]='';
            }
        }

        //prn('$to_add',$to_add,'$to_delete',$to_delete,'$to_update',$to_update);
        if(count($to_add)>0) {
            $query=Array();
            foreach($to_add as $val) $query[]="($poll_id,$site_id,'".\e::db_escape(trim($val))."')";
            $query="INSERT INTO {$table_prefix}golos_vidpovidi(pynannja_id, site_id, html) VALUES ".join(',',$query);
            //prn($query);
            \e::db_execute($query);
        }

        if(count($to_delete)>0) {
            $query=Array();
            foreach($to_add as $val) $query[]="($poll_id,$site_id,'".\e::db_escape(trim($val))."')";
            $query="DELETE FROM {$table_prefix}golos_vidpovidi
  	                  WHERE site_id={$site_id}
  	                    AND pynannja_id=$poll_id
  	                    AND id IN(".join(',',array_keys($to_delete)).")";
            //prn($query);
            \e::db_execute($query);
        }

        if(count($to_update)>0) {
            foreach($to_update as $key=>$val) {
                $query="UPDATE {$table_prefix}golos_vidpovidi
  	                     SET html='".\e::db_escape($val)."'
  	                     WHERE site_id={$site_id}
                           AND pynannja_id=$poll_id
                           AND id=$key";
                //prn($query);
                \e::db_execute($query);
            }
        }


        # ----------------- update variants - end ------------------------------
        header("Location: index.php?action=poll/edit&site_id=$site_id&poll_id=$poll_id");
        $GLOBALS['main_template_name']='';
        return;
    }



}
//------------------- save data - end ------------------------------------------

$input_vars['page_title']  =
        $input_vars['page_header'] = $this_site_info['title']
        .' - '
        .( ($poll_id>0)?$text['Poll_edit']:$text['Polls_create']);


# --------------------- draw javascript editor - begin --------------------------
$js=Array();
$html='';
$js[]="rows=[];\n";
$row_id=0;
//prn($this_poll_info['vidpovidi']);
foreach($this_poll_info['vidpovidi'] as $id=>$fld) {
    if(strlen($fld['html'])==0) continue;
    $js[]="rows[{$row_id}]=document.getElementById('vidpovidi_row_{$row_id}');\n";
    $html.="<div id=vidpovidi_row_{$row_id}>
              <input type=text
                     name=poll[vidpovidi][{$fld['id']}][html]
                     id=vidpovidi_html_{$row_id}
                     value=\"".htmlspecialchars($fld['html'])."\"
                     style='width:300px;'><input type=button value=\"{$text['Delete']}\" onclick=\"del_row('vidpovidi_row_{$row_id}')\">
              </div>\n";
    $row_id++;
}
$js[]="rows[$row_id]={$row_id};\n";
$html.="<div id=vidpovidi_row_{$row_id}>
            <input type=text
                   name=poll[vidpovidi][-$row_id][html]
                   id=vidpovidi_html_{$row_id}
                   value=\"\"
                   style='width:300px;'><input type=button value=\"{$text['Delete']}\" onclick=\"del_row('vidpovidi_row_{$row_id}')\">
            </div>\n<div id=vidpovidi_last_row><!-- <input type=button value=\"{$text['Poll_Delete_empty_rows']}\" onclick='delete_empty_rows()'> --><input type=button value=\"{$text['Poll_Add_empty_row']}\" onclick='add_row()'></div>";
$js[]="
     var last_row_id={$row_id};
     var vidpovidi_list;
     for(var i in rows)
     {
        rows[i]={row:document.getElementById('vidpovidi_row_'+i),html:document.getElementById('vidpovidi_html_'+i)};
     }

     function  delete_empty_rows()
     {
       var rw=rows;
       var j;
       if(!vidpovidi_list) vidpovidi_list=document.getElementById('vidpovidi_list');
       if(vidpovidi_list && rw[last_row_id].html.value!='' ) add_row()

       for(j in rw)
       {
       // delete empty rows
          //if(j!=last_row_id && rw[j].html.value=='')
          if(rw[j].html.value=='')
          {
             rw[j].row.innerHTML='';
             delete rw[j];
          }
       }
     }

     function del_row(row_id)
     {
        var rw=rows;
        var j;
        for(j in rw)
        {
	        if(rw[j].row.id==row_id)
	        {
	           rw[j].row.innerHTML='';
	           delete rw[j];
	        }
        }
     }

     function add_row()
     {
         var rw=rows;
         last_row_id++;
         var div = document.createElement('div');
         div.id='vidpovidi_row_'+last_row_id;
         div.innerHTML='<input type=text name=poll[vidpovidi][-'+last_row_id+'][html] id=vidpovidi_html_'+last_row_id+' value=\"\" style=\"width:300px;\"><input type=button value=\"{$text['Delete']}\" onclick=\\'del_row(\"vidpovidi_row_'+last_row_id+'\")\\'>';
         var last_row = document.getElementById('vidpovidi_last_row');
         var parentDiv = document.getElementById('vidpovidi_list');
         parentDiv.insertBefore(div,last_row);
         rw[last_row_id]={row:document.getElementById('vidpovidi_row_'+last_row_id),html:document.getElementById('vidpovidi_html_'+last_row_id)};
     }

     var map=[];
     var fr='�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�,�';        fr=fr.split(/,/);
     var to='a,b,v,g,d,e,zh,z,i,j,k,l,m,n,o,p,r,s,t,u,f,h,ts,ch,sh,sch,yu,ya,a,b,v,g,d,e,zh,z,i,j,k,l,m,n,o,p,r,s,t,u,f,h,ts,ch,sh,sch,yu,ya,y,y'; to=to.split(/,/);
     for(var i in fr) map[fr[i]]=to[i];

     function translit(s)
     {
        var str=''+s;
        var letters=str.match(/./g);
     // alert(letters);
        for(var i in letters)
        {
            if(map[letters[i]]) letters[i]=map[letters[i]];
            else letters[i]='_';
        }
     // alert(letters);
        return letters.join('');
     }
        ";
$js=join('',$js);

$js_editor="
	  <div id=vidpovidi_list style='padding-left:20px;'>
        $html
	  </div>
	  <script type=text/javascript>
        $js
	  </script>  <br>
        ";
# --------------------- draw javascript editor - end ----------------------------

$hidden_fields=hidden_form_elements('^poll');
$hidden_fields.="<input type=hidden name=poll_id value=$poll_id>";
$hidden_fields.="<input type=hidden name=poll_save value=yes>";



$input_vars['page_content']= "
<form action=index.php>
        $hidden_fields

        $messages

  <small>{$text['Poll_question']}:</small><br>
  <textarea type=text name=poll[title] style='width:320px;height:120px;'>".htmlspecialchars($this_poll_info['title'])."</textarea><br>

  <input type=checkbox name=poll[is_active] ".($this_poll_info['is_active']==1?'checked':'').">{$text['Poll_is_active']};
  <br>{$text['Poll_type']}
  <input type=radio name=poll[poll_type] value=radio    ".($this_poll_info['poll_type']!='checkbox'?'checked':'').">{$text['Poll_type_radio']}
  <input type=radio name=poll[poll_type] value=checkbox ".($this_poll_info['poll_type']=='checkbox'?'checked':'').">{$text['Poll_type_checkbox']}

  <br><br>
  <small>{$text['Poll_answer_varialnts']}:</small><br>
        $js_editor
  <input type=submit value=\"{$text['Poll_Save_changes']}\">
  </form>
        ";





//----------------------------- context menu - begin ---------------------------
$input_vars['page_menu']['page']=Array('title'=>$text['Poll'],'items'=>Array());
run('poll/functions');
$input_vars['page_menu']['page']['items'] = menu_poll($this_poll_info);

$sti=$text['Site'].' "'. $this_site_info['title'].'"';
$Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,25)."</span>";
$input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------


?>