<?php

/*
  Class Report generates report structure
 */

class report_generator extends Report {

    function draw_header($response) {
        $tor = '';
        $tor = "
        <style type=\"text/css\">
        <!--
        .menu_block{
          position:absolute;
          border:solid 1px blue;
          background-color: #e0e0e0;
          padding:5px;
          text-align:left;
        }
        a.row{display:block;padding:4px;text-decoration:none;}
        a.row:hover{background-color:yellow;}
        .cmi{display:block;white-space:nowrap;}
        -->
        </style>
        <script type=\"text/javascript\">
        <!--
          var report_prev_menu;
          var report_href;
          function report_change_state(cid)
          {
              var lay=document.getElementById(cid);
              if(lay && lay.style){
                if (lay.style.display==\"none\")
                {
                   if(report_prev_menu) report_prev_menu.style.display=\"none\";
                   lay.style.display=\"block\";
                   report_prev_menu=lay;
                }
                else
                {
                   lay.style.display=\"none\";
                   report_prev_menu=null;
                }
              }
              report_href=true;
          }

          function report_hide_menu()
          {
            if(report_prev_menu && !report_href) report_prev_menu.style.display=\"none\";
            report_href=false;
          }
          document.onclick=report_hide_menu;
        // -->
        </script>
        ";

        return $tor;
    }

    function draw_filter($response) {
        $form = '';
        // ------------------------- filter -- begin ------------------------------
        $form = "
        <style type=\"text/css\">
        .filter_element{
           display:inline-block;
           width:49.4%;
           vertical-align:top;
        }
        </style>
        <script>
        $(function() {

           $(\".datepicker\").each(function(ind,elm){
             //console.log(elm);
             var current_date_text=$(elm).val();
             $(elm).datepicker();
             $(elm).datepicker(\"option\", \"dateFormat\", \"yy-mm-dd 00:00\");
             // $(elm).datepicker( \"option\", $.datepicker.regional[ 'ru' ] );
             if(current_date_text!=''){
                var tmp=current_date_text.split(' ');
                var date=tmp[0].split('-');
                var year=date[0];
                var month=date[1]-1;
                var day=date[2];
                var hours=0,minutes=0, seconds=0, milliseconds=0;
                $(elm).datepicker(\"setDate\", new Date(year, month, day) );
                //alert(current_date_text+
                //      ' | '+ (new Date(year, month, day))+
                //      ' | '+ year +
                //      ' | '+ month +
                //      ' | '+ day);
                //$(elm).datepicker(\"setDate\", new Date(current_date_text) );

             }
           });
        });
        </script>

        ";

        foreach ($this->field as $fld) {
            if (!isset($fld['options']['hidden']))
                $fld['options']['hidden'] = '';
            if ($fld['options']['hidden'] == 'yes')
                continue;

            $form.="
         <span class=\"filter_element\">
         ";

            switch ($fld['type']) {
                case 'id':
                    $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_value'] : '';

                    $form.="
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$value}\"
                                     style=\"width:99%;\"
                                     size=3>
              ";
                    break;

                case 'string':
                    $form.="
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                     style=\"width:99%;\"
                                     size=50>
              ";
                    break;

                case 'integer':
                case 'float':
                    $value_min = isset($response['fields'][$fld['alias']]['filter']['form_element_min_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_min_value'] : '';
                    $value_max = isset($response['fields'][$fld['alias']]['filter']['form_element_max_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_max_value'] : '';

                    $form.="
                    <div>&nbsp;</div>
                    <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"{$value_min}\"
                                         size=3>
                    &le;&nbsp;{$response['fields'][$fld['alias']]['label']}&nbsp;&le;
                    <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"{$value_max}\"
                                         size=3></nobr>
              ";
                    break;
                case 'enum':
                    $form.="
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\" style=\"width:99%;\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
              ";
                    break;
                case 'unix_timestamp':
                case 'datetime':
                    if (!isset($response['fields'][$fld['alias']]['filter']['form_element_min_value']))
                        $response['fields'][$fld['alias']]['filter']['form_element_min_value'] = '';
                    if (!isset($response['fields'][$fld['alias']]['filter']['form_element_max_value']))
                        $response['fields'][$fld['alias']]['filter']['form_element_max_value'] = '';
                    $form.="
                    <div>&nbsp;</div>
                    <nobr>
                    <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                     size=13 class=\"datepicker\">
                    &le;&nbsp;{$response['fields'][$fld['alias']]['label']}&nbsp;&le;
                    <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                     size=13 class=\"datepicker\"></nobr>
              ";
                    break;
            }
            $form.="</span>";
        }
        $form.="
          <div align=right><input type=submit name=submit value=\"".text('Search')."\"></div>\n
     ";


        // ------------------------- filter -- end --------------------------------
        return $form;
    }

    function draw_table_headers($response){
        $tor='';
        // ------------------------- header -- begin ------------------------------
        $tor.="<tr><th align=center valign=top></th>\n";
        foreach ($this->field as $fld) {
            if (!isset($fld['options']['hidden']))
                $fld['options']['hidden'] = '';
            if ($fld['options']['hidden'] == 'yes')
                continue;
            $tor.="
              <th align=center valign=bottom>
               <b>{$response['fields'][$fld['alias']]['label']}</b><br>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\">V</a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\">&Lambda;</a>
               </th>
               \n";
        }
        $tor.="</tr>\n";
        // ------------------------- header -- end --------------------------------
        return $tor;
    }

    function draw_context_menu($row_id,$row){
        $tor='';
        //--------------------------- context menu - begin ---------------------
        if (is_array($row['context_menu'])) {
                $tor.="<img src=\"img/context_menu.gif\" border=1px alt=\"\" onclick=\"report_change_state('cm{$row_id}')\">
                       <div class=menu_block style='display:none;' id='cm{$row_id}'>";
                foreach ($row['context_menu'] as $menu_item) {
                    $tor.="<a class=\"cmi\" href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a>\n";
                }
                $tor.="</div>";
        } else {
                $tor.=$row['context_menu'];
        }
        //--------------------------- context menu - end -----------------------
        return $tor;
    }

    function draw_rows($response){
        $tor='';
        // ------------------------- rows -- begin -----------------------------
        foreach ($response['rows'] as $row_id => $row) {
            $tor.="<tr class=row>\n";
            // ------------ context menu - begin -------------------------------
            $tor.="<td align=center valign=top>\n";
            $tor.=$this->draw_context_menu($row_id, $row);
            $tor.="</td>\n";
            // ------------ context menu - end ---------------------------------

            // ------------ other visible fields - begin -----------------------
            foreach ($this->field as $fld) {
                if (!isset($fld['options']['hidden'])) $fld['options']['hidden'] = 'no';
                if ($fld['options']['hidden'] == 'yes') continue;

                if(isset($fld['view'])){
                    $tor.="<td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">".$fld['view']($row)."</a></td>\n";
                }else{
                    $tor.="<td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row[$fld['alias']]}</a></td>\n";
                }

            }
            // ------------ other visible fields - end -------------------------

            $tor.="\n</tr>\n\n";
        }
        // ------------------------- rows -- end -------------------------------
        return $tor;
    }

    function draw_paging($response){
        $tor='';
        // ------------------------- paging - begin ----------------------------
        $fld_cnt = count($this->field) + 1;
        $tor.="<tr>\n<td colspan=\"{$fld_cnt}\" align=center>\n".text('Pages')." :\n";
        foreach ($response['pages'] as $pg){
            $tor.="<a href=\"{$pg['page_url']}\">{$pg['page_id']}</a>&nbsp;";
        }
        $tor.='<br>';
        if (strlen($response['backward']) > 0){
            $tor.="&nbsp;<a href=\"{$response['backward']}\">".text('Previous_page')."</a>&nbsp;";
        }
        if (strlen($response['forward']) > 0){
            $tor.="&nbsp;<a href=\"{$response['forward']}\">".text('Next page')."</a>&nbsp;";
        }
        $tor.="\n</td>\n</tr>\n\n";
        // ------------------------- paging - end ------------------------------
        // ------------------------- number of rows - begin --------------------
        $tor.="<tr><td colspan=\"{$fld_cnt}\" align=center>{$response['total_rows']} ".text('rows found')."</td></tr>\n\n";
        // ------------------------- number of rows - end ----------------------

        return $tor;
    }

    function draw_default_list($response) {
        global $text;

        $tor=$this->draw_header($response);

        $tor.="<form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
               {$response['hidden_fields']}";

        $tor.=$this->draw_filter($response);

        $tor.="<table border=1>";
        $tor.=$this->draw_table_headers($response);
        $tor.=$this->draw_rows($response);
        $tor.=$this->draw_paging($response);
        $tor.="</table>\n";
        $tor.="</form>\n";
        return $tor;
    }
}

?>