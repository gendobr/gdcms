<?php

/*
 * manage news subscriptions
 */

$debug = false;
run('site/menu');

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
//
//
//
// =============================================================================


if(isset($input_vars['add_email'])
        && strlen(trim($input_vars['add_email']))>0
        && is_valid_email(trim($input_vars['add_email']))){
            // insert new subscriber
            $query="INSERT INTO {$table_prefix}news_subscriber(
                    news_subscriber_name,
                    news_subscriber_email,
                    news_subscriber_code,
                    news_subscriber_is_valid,
                    news_subscriber_date,
                    site_id
                    ) VALUES (
                    '".  \e::db_escape(trim($input_vars['add_name']))."',
                    '".  \e::db_escape(trim($input_vars['add_email']))."',
                    '',
                    1,
                    NOW(),
                    $site_id
                )";
            \e::db_execute($query);
}

run("lib/class_report");
run("lib/class_report_extended");
$re = new report_generator;
$re->db = $db;
$re->distinct = false;
$re->exclude="^add_";

$re->from = "{$table_prefix}news_subscriber AS news_subscriber";

$re->add_where(" news_subscriber.site_id=$site_id ");

$re->add_field(
        // field:
          'news_subscriber.news_subscriber_id'
        // alias:
        , 'news_subscriber_id'
        // type:
        , 'id:hidden=no'
        // label:
        , '#'
        // group_operation:
        , false);

$re->add_field(
        // field:
       'news_subscriber.news_subscriber_name'
        // alias:
      ,'news_subscriber_name'
        // type:
      , 'string'
        // label:
      , text('News_subscriber_name')
        // group_operation:
      , false);

$re->field['news_subscriber_name']['view']='editable_news_subscriber_name';
function editable_news_subscriber_name($row){
    return "<div class=\"editable_news_subscriber_name editable_news_subscriber\" id=news_subscriber_name_{$row['news_subscriber_id']}>".$row['news_subscriber_name'].'</div>';
}

$re->add_field(
          // field:
         'news_subscriber.news_subscriber_email'
          // alias:
        , 'news_subscriber_email'
          // type:
        , 'string'
          // label:
        , text('News_subscriber_email')
          // group_operation:
        , false);
$re->field['news_subscriber_email']['view']='editable_news_subscriber_email';
function editable_news_subscriber_email($row){
    return "<div class=\"editable_news_subscriber_email editable_news_subscriber\" id=news_subscriber_email_{$row['news_subscriber_id']}>".$row['news_subscriber_email'].'</div>';
}
$re->add_field(
          // field:
         "news_subscriber.news_subscriber_is_valid"
          // alias:
        , 'news_subscriber_is_valid'
          // type:
        , "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        // label:
        , text('news_subscriber_is_valid')
        // group_operation:
        , false);
$re->field['news_subscriber_is_valid']['view']='editable_news_subscriber_is_valid';
function editable_news_subscriber_is_valid($row){
    return "<select class=news_subscriber_is_valid_editor id=news_subscriber_is_valid_{$row['news_subscriber_id']}>".draw_options($row['news_subscriber_is_valid_value'], Array(1=>text('positive_answer'),0=>text('negative_answer'))).'</select>';
}

$re->add_field(
          // field:
         'news_subscriber.news_subscriber_date'
          // alias:
        , $alias = 'news_subscriber_date'
          // type:
        , $type = 'datetime'
          // label:
        , $text['Date']
          // group_operation:
        , false);

$re->add_field($field = 'news_subscriber.site_id'
        , $alias = 'site_id'
        , $type = 'id:hidden=yes'
        , $label = text('Site_id')
        , $_group_operation = false);
//prn($re->create_query());
$response = $re->show();
//prn($response);
//prn($re);
// =============================================================================
//
//
//--------------------------- context menu -- begin ----------------------------
//run('news/menu');
$cnt = count($response['rows']);
for ($i = 0; $i < $cnt; $i++) {
    $response['rows'][$i]['context_menu'] = "<a href=\"javascript:void(0)\" onclick=\"if(confirm('Are You sure?')) delete_subscriber({$response['rows'][$i]['news_subscriber_id']})\">".text('Delete')."</a>";
//    //--------------------------- context menu -- begin ------------------------
//    $response['rows'][$i]['context_menu'] = menu_news($response['rows'][$i]);
//    $response['rows'][$i]['category_id'] = wordwrap($response['rows'][$i]['category_id'], 10, " ", true);
//    $response['rows'][$i]['tags'] = wordwrap($response['rows'][$i]['tags'], 10, "&shy;", true);
//    //--------------------------- context menu -- end --------------------------
}
////--------------------------- context menu -- end ------------------------------
$input_vars['page_title'] = $this_site_info['title'] . ' - ' . text('List_of_news_subscribers');
$input_vars['page_header'] = $input_vars['page_title'];
$input_vars['page_content'] = "
<style>
.editable_news_subscriber{
  width:120pt;
}
.editable_news_subscriber_name, .editable_news_subscriber_email{
  height:40px;
}
</style>
<script type=\"text/javascript\" src=\"scripts/lib/jquery.jeditable.mini.js\"></script>
<script type=\"text/javascript\">
 $(document).ready(function() {
     $('.editable_news_subscriber_name').editable('index.php?action=news_subscription/set_news_subscriber_name',{
        cssclass : 'editable_news_subscriber',
        indicator : 'Saving...',
        tooltip   : 'Click to edit...'
     });
     $('.editable_news_subscriber_email').editable('index.php?action=news_subscription/set_news_subscriber_email',{
        cssclass : 'editable_news_subscriber',
        indicator : 'Saving...',
        tooltip   : 'Click to edit...'
     });
     var news_subscriber_is_valid_changed=function(e){
          e.preventDefault();
          var new_value=$(this).val();
          var element_id=$(this).attr('id');
          // console.log(' element_id='+element_id+' value='+new_value );
          //
          $.ajax( {
                type: 'POST',
                url: 'index.php?action=news_subscription/set_news_subscriber_is_valid',
                data: {id:element_id,value:new_value}
          });
       };
     $('.news_subscriber_is_valid_editor').each(function(ind,elm){
       $(elm).change(news_subscriber_is_valid_changed)
     });
 });

function delete_subscriber(id){
   //console.log('delete_subscriber('+id+')');
   $.ajax( {
     type: 'POST',
     url: 'index.php?action=news_subscription/delete_news_subscriber',
     data: {id:id},
     success:function(){window.location.reload();}
   });
}
</script>
".$re->draw_default_list($response)
."<br>
    <h3>".text('Add_user')."</h3>
    <form action=\"index.php\" method=\"post\">
    <input type=\"hidden\" name=\"action\" value=\"news_subscription/subscribers\">
    <input type=\"hidden\" name=\"site_id\" value=\"{$site_id}\">
    ".text('News_subscriber_name').":
    <input type=\"text\" name=\"add_name\" value=\"\">&nbsp;&nbsp;
    ".text('News_subscriber_email').":
    <input type=\"text\" name=\"add_email\" value=\"\">&nbsp;&nbsp;
    <input type=\"submit\" value=\"".text('Add_user')."\">
    </form>
   ";

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>