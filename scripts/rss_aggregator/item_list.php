<?php
/**
 * manage imported RSS items
 */
run('site/menu');
run('rss_aggregator/functions');




//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn('$this_site_info=',$this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------

//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended_1");

  class rss_report_generator extends report_generator{
    function draw_rows($response) {
        $tor = '';
        // ------------------------- rows -- begin --------------------------------

        //$row['rsssourceitem_is_visiblle_value']

        //$fld_cnt = count($this->field);
        foreach ($response['rows'] as $row_id => $row) {
            //prn($row);
            $row_style=($row['rsssourceitem_is_visiblle_value']=='0')?'style="color:gray;"':'style="color:black;"';
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
                $tor.="<tr><td align=left valign=top width=150px class=report_field_label><nobr>{$response['fields'][$fld['alias']]['label']}</nobr></td><td align=left valign=top $row_style>{$row[$fld['alias'].'_html']}</td>\n</tr>\n";
            }
            $tor.="</table><br><br></td>\n";
            $tor.="\n</tr>\n\n";
        }

        // ------------------------- rows -- end ----------------------------------
        return $tor;
    }

  }



  $re=new rss_report_generator;

  $re->distinct=false;
  $re->from="
   <<tp>>rsssource as rsssource
   INNER JOIN <<tp>>rsssourceitem AS rsssourceitem
   ON ( rsssource.rsssource_id=rsssourceitem.rsssource_id AND rsssourceitem.site_id=$site_id )
  ";
  $re->add_where(" rsssource.site_id=$site_id ");

//rsssourceitem_id           bigint(20)
  $re->add_field( $field='rsssourceitem.rsssourceitem_id'
                 ,$alias='rsssourceitem_id'
                 ,$type ='id'
                 ,$label=text('rsssourceitem_id')
                 ,$_group_operation=false);

//rsssource_id               bigint(20)
  $re->add_field( $field='rsssourceitem.rsssource_id'
                 ,$alias='rsssource_id'
                 ,$type ='id:hidden=yes'
                 ,$label=text('rsssource_id')
                 ,$_group_operation=false);

//site_id                    bigint(20)
  $re->add_field( $field='rsssourceitem.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label=text('site_id')
                 ,$_group_operation=false);

//rsssourceitem_lang         varchar(3)
  $LL = join('&',\e::db_get_associated_array("SELECT rsssourceitem_lang,CONCAT(rsssourceitem_lang,'=',rsssourceitem_lang) FROM <<tp>>rsssourceitem WHERE site_id={$site_id}"));
  $re->add_field( $field='rsssourceitem.rsssourceitem_lang'
                 ,$alias='rsssourceitem_lang'
                 ,$type ='enum:'.$LL
                 ,$label=text('rsssourceitem_lang')
                 ,$_group_operation=false);

//rsssourceitem_datetime     datetime
  $re->add_field($field = 'rsssourceitem.rsssourceitem_datetime'
                 , $alias = 'rsssourceitem_datetime'
                 , $type = 'datetime'
                 , $label = text('Date')
                 , $_group_operation = false);

//rsssourceitem_is_visiblle  tinyint(1)
  $re->add_field($field = "rsssourceitem.rsssourceitem_is_visiblle"
                 , $alias = 'rsssourceitem_is_visiblle'
                 , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 , $label = text("rsssourceitem_is_visiblle")
                 , $_group_operation = false);

//rsssource_title
  $re->add_field( $field='rsssource.rsssource_title'
                 ,$alias='rsssource_title'
                 ,$type ='string'
                 ,$label=text('rsssource_title')
                 ,$_group_operation=false);

//rsssourceitem_title        varchar(512)
  $re->add_field( $field='rsssourceitem.rsssourceitem_title'
                 ,$alias='rsssourceitem_title'
                 ,$type ='string'
                 ,$label=text('rsssourceitem_title')
                 ,$_group_operation=false);

//rsssourceitem_abstract     text
  $re->add_field( $field='rsssourceitem.rsssourceitem_abstract'
                 ,$alias='rsssourceitem_abstract'
                 ,$type ='string'
                 ,$label=text('rsssourceitem_abstract')
                 ,$_group_operation=false);

//rsssourceitem_url          varchar(4096)
  $re->add_field( $field='rsssourceitem.rsssourceitem_url'
                 ,$alias='rsssourceitem_url'
                 ,$type ='string'
                 ,$label=text('rsssourceitem_url')
                 ,$_group_operation=false);

//rsssourceitem_src          text
//rsssourceitem_guid         varchar(4096)
//rsssourceitem_hash         varchar(128)
  unset($field,$alias,$type,$label, $_group_operation);
  // prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------
//--------------------------- adjust list -- begin -----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++) {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_rsssourceitem($response['rows'][$i]);

        $response['rows'][$i]['rsssourceitem_id_html']          = &$response['rows'][$i]['rsssourceitem_id'];
        $response['rows'][$i]['rsssource_id_html']              = &$response['rows'][$i]['rsssource_id'];
        $response['rows'][$i]['site_id_html']                   = &$response['rows'][$i]['site_id'];
        $response['rows'][$i]['rsssourceitem_lang_html']        = &$response['rows'][$i]['rsssourceitem_lang'];
        $response['rows'][$i]['rsssourceitem_datetime_html']    = &$response['rows'][$i]['rsssourceitem_datetime'];
        $response['rows'][$i]['rsssourceitem_is_visiblle_html'] = &$response['rows'][$i]['rsssourceitem_is_visiblle'];
        $response['rows'][$i]['rsssource_title_html']           = &$response['rows'][$i]['rsssource_title'];
        $response['rows'][$i]['rsssourceitem_title_html']       = strip_tags($response['rows'][$i]['rsssourceitem_title']);
        $response['rows'][$i]['rsssourceitem_abstract_html']    = strip_tags($response['rows'][$i]['rsssourceitem_abstract']);
        $response['rows'][$i]['rsssourceitem_url_html']         = "<a href=\"".$response['rows'][$i]['rsssourceitem_url']."\">".  shorten($response['rows'][$i]['rsssourceitem_url'],60).'</a>';


        //$response['rows'][$i]['rsssource_last_updated']= "<nobr>{$response['rows'][$i]['rsssource_last_updated']}</nobr>";
        //$response['rows'][$i]['rsssource_url_value']= $response['rows'][$i]['rsssource_url'];
        //$response['rows'][$i]['rsssource_url']= "<a href=\"{$response['rows'][$i]['rsssource_url']}\">".  shorten($response['rows'][$i]['rsssource_url'],30)."</a>";


      //--------------------------- context menu -- end --------------------------
    }

    // prn($response);
//--------------------------- adjust list -- end -------------------------------





$input_vars['page_title']  =
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('rsssourceitem_list');
$input_vars['page_content']= $re->draw_default_list($response);



//--------------------------- context menu -- begin ----------------------------
  $sti=text('Site').' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------


?>