<?php
global $main_template_name; $main_template_name='';

if(isset($input_vars['interface_lang'])) if(strlen($input_vars['interface_lang'])>0) $input_vars['lang']=$input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);

run('forum/functions');

$_SESSION['site_visitor_info']=$GLOBALS['default_site_visitor_info'];
echo "
          <script>
            window.opener.location.reload();
            window.close();
          </script>
      ";

// remove from history
   nohistory($input_vars['action']);


?>
