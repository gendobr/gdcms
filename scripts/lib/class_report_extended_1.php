<?php

/*
  Class Report generates report structure
 */

class report_generator extends Report {

    function draw_filter($response) {
        global $text;
        $tor = '';
// ------------------------- filter -- begin ------------------------------
        $tor.="<table border=1 width=100%>";
        foreach ($this->field as $fld) {
            if (isset($fld['options']['hidden']) && $fld['options']['hidden'] == 'yes')
                continue;
            $tor.="
             <tr>
              <td align=right valign=top>
               <nobr>
               <b>{$response['fields'][$fld['alias']]['label']}</b>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\">V</a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\">&Lambda;</a>
               </nobr>
               </td>
               \n";

            switch ($fld['type']) {
                case 'id':
                    $tor.="
                    <td align=left valign=top>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"" . (isset($response['fields'][$fld['alias']]['filter']['form_element_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_value'] : '') . "\"
                                     size=3>
                    </td>
              ";
                    break;

                case 'string':
                    $tor.="
                    <td align=left valign=top>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\">
                    </td>
              ";
                    break;

                case 'integer':
                case 'float':
                    $tor.="
                    <td align=left valign=top>
                    <nobr>
                    &ge;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                         size=3>
                    &le;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                         size=3>
                    </nobr>
                    </td>
              ";
                    break;
                case 'enum':
                    $tor.="
                    <td align=left valign=top>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
                    </td>
              ";
                    break;
                case 'unix_timestamp':
                case 'datetime':
                    $tor.="
                    <td align=left>
                    <nobr>&ge;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"" . (isset($response['fields'][$fld['alias']]['filter']['form_element_min_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_min_value'] : '') . "\"
                                         size=17>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_min_name']})\">
                    &le;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"" . (isset($response['fields'][$fld['alias']]['filter']['form_element_max_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_max_value'] : '') . "\"
                                         size=17>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_max_name']})\"></nobr>
                    </td>
              ";
                    break;
            }
        }
        $tor.="</tr>
            <tr><td></td><td valign=top align=left><input type=submit name=submit value=\"{$text['Search']}\"></td>
            </table>";
        // ------------------------- filter -- end --------------------------------
        return $tor;
    }

    function draw_rows($response) {
        $tor = '';
        // ------------------------- rows -- begin --------------------------------

        $fld_cnt = count($this->field);
        foreach ($response['rows'] as $row_id => $row) {
            $tor.="<tr>\n";

            //--------------------------- context menu - begin ----------------------
            $tor.="<td align=center valign=top width=20px style='padding:6px;'>\n";
            if (is_array($row['context_menu'])) {
                $tor.="<img src=\"img/context_menu.gif\" border=1px alt=\"\" onclick=\"report_change_state('cm{$row_id}')\">
                       <div class=menu_block style='display:none;' id='cm{$row_id}'>";
                foreach ($row['context_menu'] as $menu_item) {
                    $tor.="<nobr><a href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a></nobr><br/>\n";
                }
                $tor.="</div>";
            }
            $tor.="</td>\n";
            //--------------------------- context menu - end ------------------------

            $tor.="<td align=center valign=top><table border=0 width=100%>\n";
            foreach ($this->field as $fld) {
                if (isset($fld['options']['hidden']) && $fld['options']['hidden'] == 'yes')
                    continue;
                $tor.="<tr><td align=left valign=top width=150px class=report_field_label><nobr>{$response['fields'][$fld['alias']]['label']}</nobr></td><td align=left valign=top>{$row[$fld['alias']]}</td>\n</tr>\n";
            }
            $tor.="</table><br><br></td>\n";
            $tor.="\n</tr>\n\n";
        }

        // ------------------------- rows -- end ----------------------------------
        return $tor;
    }

    function draw_paging($response) {
        $tor = '';
        // ------------------------- paging - begin -------------------------------
        $tor.="<tr>\n<td colspan=\"2\" align=center>\n".text('Pages')." :\n";

        foreach ($response['pages'] as $pg)
            $tor.="<a href=\"{$pg['page_url']}\">{$pg['page_id']}</a>&nbsp;";

        $tor.='<br>';
        if (strlen($response['backward']) > 0)
            $tor.="&nbsp;<a href=\"{$response['backward']}\">" . text('Previous_page') . "</a>&nbsp;";
        if (strlen($response['forward']) > 0)
            $tor.="&nbsp;<a href=\"{$response['forward']}\">" . text('Next page') . "</a>&nbsp;";


        $tor.="\n</td>\n</tr>\n\n";
        // ------------------------- paging - end ---------------------------------
        // ------------------------- list is empty - begin ------------------------
        $tor.="<tr><td colspan=\"2\" align=center>{$response['total_rows']} " . text('rows found') . "</td></tr>\n\n";
        // ------------------------- list is empty - end --------------------------
        return $tor;
    }

    function draw_default_list($response) {
        global $text;
        $tor = "
      <style type=\"text/css\">
      <!--
      .menu_block
      {
        position:absolute;
        border:solid 1px blue;
        background-color: #e0e0e0;
        padding:5px;
        text-align:left;
      }
      .report_field_label{color:silver;}
      table.report_rows td{border:none;}
      -->
      </style>
      <script type=\"text/javascript\">
      <!--
        var report_prev_menu;
        var report_href;
        function report_change_state(cid)
        {
            var lay=document.getElementById(cid);
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
      <!-- script type=\"text/javascript\" src=\"scripts/lib/popupcalend/calendar.js\"></script -->

       <form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
       {$response['hidden_fields']}\n";

        $tor.=$this->draw_filter($response);
        $tor.="<table border=1px width=100% class=report_rows>";
        $tor.=$this->draw_rows($response);
        $tor.=$this->draw_paging($response);
        $tor.="</table>\n</form>\n";
        return $tor;
    }

}

?>