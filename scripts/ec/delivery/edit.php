<?php
/*
 * Edit delivery settings for the selected site shop
 */

run('site/menu');
# ------------------- get site info - begin ------------------------------------
  $site_id=(int)(isset($input_vars['site_id'])?$input_vars['site_id']:0);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) exit('Site not found');
# ------------------- get site info - end --------------------------------------

# ------------------- check permission - begin ---------------------------------
  if(get_level($site_id)<=0)
  {
     $input_vars['page_title']  =
     $input_vars['page_header'] =
     $input_vars['page_content']=text('Access_denied');
     return 0;
  }
# ------------------- check permission - end -----------------------------------

run('ec/delivery/functions');

# ------------------- delete condition - begin ---------------------------------
  if(isset($input_vars['delete_ec_delivery_id'])){
      $query="DELETE FROM {$table_prefix}ec_delivery WHERE ec_delivery_id=".((int)$input_vars['delete_ec_delivery_id'])." AND site_id=$site_id";
      db_execute($query);
  }
# ------------------- delete condition - end -----------------------------------

# ------------------- update delivery config - begin ---------------------------
  $msg='';
  if(isset($input_vars['delivery']) && is_array($input_vars['delivery'])){
      // prn('Saving ...',$input_vars['delivery']);

      // -------------- add new condition - begin ------------------------------
         if(strlen(trim($input_vars['delivery'][0]['ec_delivery_title']))>0){
             $ec_delivery_title=trim($input_vars['delivery'][0]['ec_delivery_title']);
             $ec_delivery_ordering=abs((int)$input_vars['delivery'][0]['ec_delivery_ordering']);
             $ec_delivery_indent=abs((int)$input_vars['delivery'][0]['ec_delivery_indent']);
             $ec_delivery_cost=trim($input_vars['delivery'][0]['ec_delivery_cost']);
             $ec_delivery_condition=trim($input_vars['delivery'][0]['ec_delivery_condition']);
             $query="INSERT INTO {$table_prefix}ec_delivery(
                        site_id,ec_delivery_title,ec_delivery_ordering,
                        ec_delivery_indent,ec_delivery_cost,ec_delivery_condition)
                     VALUES(
                        {$site_id},'".DbStr($ec_delivery_title)."',
                        $ec_delivery_ordering,$ec_delivery_indent,
                        '".DbStr($ec_delivery_cost)."','".DbStr($ec_delivery_condition)."'
                     )";
             db_execute($query);
         }
         unset($input_vars['delivery'][0]);
      // -------------- add new condition - end --------------------------------
      // -------------- update - begin -----------------------------------------
         foreach($input_vars['delivery'] as $de){
             $ec_delivery_id=(int) $de['ec_delivery_id'];
             $ec_delivery_title=trim($de['ec_delivery_title']);
             $ec_delivery_ordering=abs((int)$de['ec_delivery_ordering']);
             $ec_delivery_indent=abs((int)$de['ec_delivery_indent']);
             $ec_delivery_cost=trim($de['ec_delivery_cost']);
             $ec_delivery_condition=trim($de['ec_delivery_condition']);
             $query="UPDATE {$table_prefix}ec_delivery
                     SET ec_delivery_title= '".DbStr($ec_delivery_title)."',
                         ec_delivery_ordering=$ec_delivery_ordering,
                         ec_delivery_indent=$ec_delivery_indent,
                         ec_delivery_cost='".DbStr($ec_delivery_cost)."',
                         ec_delivery_condition='".DbStr($ec_delivery_condition)."'
                     WHERE ec_delivery_id=$ec_delivery_id AND site_id=$site_id
                     ";
             //prn(checkStr($query));
             db_execute($query);
         }
      // -------------- update - end -------------------------------------------
      $msg.='<div style="color:green">'.text('Changes_saved_successfully').'</div>';
  }


















# ------------------- update delivery config - end -----------------------------





















# ------------------- get delivery config - begin ------------------------------
  $delivery_config=db_getrows("SELECT * FROM {$table_prefix}ec_delivery WHERE site_id={$site_id} ORDER BY ec_delivery_ordering ASC");
# ------------------- get delivery config - end --------------------------------





# ------------------- draw - begin ---------------------------------------------
$input_vars['page_title']  =
$input_vars['page_header'] = text('EC_Delivery_Configurartion');
$input_vars['page_content']='
<style type="text/css">
.ordering{background-image:url(img/ordering_arrows.gif);background-position:center right;background-repeat:no-repeat;}
.indent{background-image:url(img/indent_arrows.gif);background-position:center right;background-repeat:no-repeat;}
.ttl{display:inline-block;vertical-align:top;}
span.ttl input{width:100%;}
</style>

<big><b>'.$msg.'</b></big>
<form action="'.site_root_URL.'/index.php">
    <input type=hidden name=action value=ec/delivery/edit>
    <input type=hidden name=site_id value="'.$site_id.'">
';


if(count($delivery_config)>0){
    $indent=40;
    $input_vars['page_content'].="
    <h3 style=\"text-align:left;\">".text('ec_delivery_settings')."</h3>
    <nobr>
    <input type=hidden
           name=\"delivery[0][ec_delivery_id]\"
           value=\"0\">
    <span class=ttl style=\"width:70px;\"><small>".text('ec_delivery_num')."</small></span>
    <span class=ttl style=\"width:{$indent}px;\"><small>".text('ec_delivery_indent')."</small><br><small>&nbsp;</small><br></span>
    <span class=ttl style=\"width:".(330-$indent)."px;\">
    <small>".text('ec_delivery_title')."</small><br></span>
    <span class=ttl style=\"width:180px;\">
    <small>".text('ec_delivery_cost')."</small><br></span>
    <span class=ttl style=\"width:180px;\">
    <small>".text('ec_delivery_condition')."</small><br><br></span>
    </nobr><br/>
    ";

}


$js="
    <script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>
    <script type=\"text/javascript\">
    ";
$delete_url_prefix=site_root_URL."/index.php?action=ec/delivery/edit&site_id={$site_id}&delete_ec_delivery_id=";
foreach($delivery_config as $dc){
    $indent=(40+20*$dc['ec_delivery_indent']);

    $input_vars['page_content'].="
    <nobr>
    <input type=hidden
           name=\"delivery[{$dc['ec_delivery_id']}][ec_delivery_id]\"
           value=\"{$dc['ec_delivery_id']}\">
    <span class=ttl style=\"width:70px;\"><br>
    <input type=text size=3 class='ordering'
           name=\"delivery[{$dc['ec_delivery_id']}][ec_delivery_ordering]\"
           value=\"{$dc['ec_delivery_ordering']}\">
    </span>
    <span class=ttl style=\"width:{$indent}px;\"><br>
    <input type=text class='indent'  style=\"width:".$indent."px;\"
           name=\"delivery[{$dc['ec_delivery_id']}][ec_delivery_indent]\"
           value=\"{$dc['ec_delivery_indent']}\">
    </span>
    <span class=ttl>
    <input type=text style=\"width:".(330-$indent)."px;\"  id=ttl_{$dc['ec_delivery_id']}
           name=\"delivery[{$dc['ec_delivery_id']}][ec_delivery_title]\"
           value=\"{$dc['ec_delivery_title']}\"></span>
    <span class=ttl style=\"width:180px;\"><br>
    <input type=text
           name=\"delivery[{$dc['ec_delivery_id']}][ec_delivery_cost]\"
           value=\"{$dc['ec_delivery_cost']}\">
    ".(is_valid_delivery_cost($dc['ec_delivery_cost'])?'':'<div style="color:red;font-weight:bold;">ERROR^^^</div>')."
    </span>
    <span class=ttl style=\"width:180px;\"><br>
    <input type=text
           name=\"delivery[{$dc['ec_delivery_id']}][ec_delivery_condition]\"
           value=\"{$dc['ec_delivery_condition']}\"><br>
    ".(is_valid_delivery_condition($dc['ec_delivery_condition'])?'':'<div style="color:red;font-weight:bold;">ERROR^^^</div>')."
    </span>
    <span class=ttl style=\"width:20px;\"><br>
           <a href=\"{$delete_url_prefix}{$dc['ec_delivery_id']}\" title=\"Delete\"><img src=\"img/icon_delete1.gif\" alt=\"Delete\"></a>
    </span>
    </nobr><br/>
    ";
  $js.="
          draw_langstring(\"ttl_{$dc['ec_delivery_id']}\");
    ";
}
    $indent=40;
    $input_vars['page_content'].="
    <h3 style='text-align:left;'>".text('ec_delivery_new_rule')."</h3>
    <nobr>
    <input type=hidden
           name=\"delivery[0][ec_delivery_id]\"
           value=\"0\">
    <span class=ttl style=\"width:70px;\">
    <small>".text('ec_delivery_num')."</small><br>
    <input type=text size=3 class='ordering'
           name=\"delivery[0][ec_delivery_ordering]\"
           value=\"\"></span>
    <span class=ttl style=\"width:{$indent}px;\">
    <small>".text('ec_delivery_indent')."</small><br><small>&nbsp;</small><br>
    <input type=text class='indent'
           name=\"delivery[0][ec_delivery_indent]\"
           value=\"\"></span>
    <span class=ttl style=\"width:".(330-$indent)."px;\">
    <small>".text('ec_delivery_title')."</small><br>
    <input type=text  id=ttl_0
           name=\"delivery[0][ec_delivery_title]\"
           value=\"\"></span>
    <span class=ttl style=\"width:180px;\">
    <small>".text('ec_delivery_cost')."</small><br><br>
    <input type=text
           name=\"delivery[0][ec_delivery_cost]\"
           value=\"\"><br>
           ".text('ec_delivery_cost_tip')."
    </span>
    <span class=ttl style=\"width:180px;\">
    <small>".text('ec_delivery_condition')."</small><br><br>
    <input type=text
           name=\"delivery[0][ec_delivery_condition]\"
           value=\"\$total>0\"><br>
           ".text('ec_delivery_condition_tip')."
    </span>
    </nobr>

    <br/><br/><br/><br/>
    <input type=submit value=\"".text('Save')."\">
    </form>
    ";
$js.='
          draw_langstring("ttl_0");
    </script>
    ';

 $input_vars['page_content'].=$js;
# ------------------- draw - end -----------------------------------------------

# ----------------------------- context menu - begin ---------------------------
//  $input_vars['page_menu']['page']=Array('title'=>text('EC_delivery'),'items'=>Array());
//  $input_vars['page_menu']['page']['items'] = menu_ec_item($this_ec_item_info);

  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());

  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

# ----------------------------- context menu - end -----------------------------

?>