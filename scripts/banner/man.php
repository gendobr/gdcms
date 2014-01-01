<?php
run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if($this_site_info['id']<=0) {
    $input_vars['page_title']  =$text['Site_not_found'];
    $input_vars['page_header'] =$text['Site_not_found'];
    $input_vars['page_content']=$text['Site_not_found'];
    return 0;
}
//------------------- get permission - begin -----------------------------------
$user_cense_level=get_level($this_site_info['id']);
if($user_cense_level<=0) {
    $input_vars['page_title']  =$text['Access_denied'];
    $input_vars['page_header'] =$text['Access_denied'];
    $input_vars['page_content']=$text['Access_denied'];
    return 0;
}
//------------------- get permission - end -------------------------------------

//------------------- site info - end ------------------------------------------

    $input_vars['page_title']  =
    $input_vars['page_header'] ='Banner rotator';


    $banner_rotator_url=site_public_URL."/index.php?action=banner%2Frotator&site_id={$input_vars['site_id']}&amp;lang=ukr";
    $input_vars['page_content']=str_replace('{url}',$banner_rotator_url,text('banner_rotator_manual'));

$input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>