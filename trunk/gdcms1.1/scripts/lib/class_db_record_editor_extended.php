<?

class extended_db_record_editor extends db_record_editor
{

  function draw($form)
  {
     global $_SERVER,$text;
     $tor="

     {$form['messages']}

     <script language=\"JavaScript\" src=\"scripts/lib/calendar/calendar.js\"></script>


     <form action=\"{$form['action']}\" name=\"{$form['name']}\" method=\"{$form['method']}\" enctype=\"multipart/form-data\">
     {$form['hidden_elements']}
     \n";
     // ------------------------- form -- begin --------------------------------
     //!$this->field
     foreach($form['elements'] as $fld)
     {
        //if(!isset($fld['options']['hidden'])) $fld['options']['hidden']='no';
        if(!isset($fld['comments'])) $fld['comments']='';
        if(isset($fld['options']['hidden']) && $fld['options']['hidden']=='yes')
        {
           $tor.="<input type=hidden name=\"{$fld['form_element_name']}\" value=\"{$fld['form_element_value']}\">\n";
        }
        else
        {
          if(!isset($fld['comments'])) $fld['comments']='';
          if(!isset($fld['before'])) $fld['before']='';
          if(!isset($fld['after'])) $fld['after']='';
          switch($fld['type'])
          {
            case 'enum':

               if(count($fld['options'])>3)
                  $tor.="<div class=label>{$fld['label']}</div><div class=big>{$fld['before']}<select name=\"{$fld['form_element_name']}\" id=\"{$fld['form_element_name']}\"><option value=\"\"> </option>{$fld['form_element_options']}</select>{$fld['after']}</div><div class=comments>{$fld['comments']}</div>\n";
               else
                  $tor.="<div class=label>{$fld['label']}</div><div class=big>{$fld['before']}".draw_radio($fld['value'],$fld['options'],$fld['form_element_name'])."{$fld['after']}</div><div class=comments>{$fld['comments']}</div>\n";
            break;

            case 'datetime':
            case 'unix_timastamp':
              $tor.="<div class=label>{$fld['label']}</div>
                     <div class=big>
                        {$fld['before']}
                        <input type=text name=\"{$fld['form_element_name']}\" id=\"{$fld['form_element_name']}\" value=\"{$fld['form_element_value']}\">
                        <script type=\"text/javascript\">
                        <!--
                        //var {$fld['form_element_name']}_calendar;
                        attach_calendar_to('{$fld['form_element_name']}','{$fld['form_element_name']}_calendar');
                        // -->
                        </script>
                        {$fld['after']}
                     </div>
                     <div class=comments>{$fld['comments']}</div>\n";
            break;
            case 'custom':
               $tor.="<div class=label>{$fld['label']}</div><div class=big>{$fld['form_element_html']}</div><div class=comments>{$fld['comments']}</div>\n";
            break;
            default:
               if(!isset($fld['options']['maxlength'])) $fld['options']['maxlength']=0;
               $maxlength=$this->checkInt($fld['options']['maxlength']);
               if($maxlength>0)
               {
                  $str_max_len=" maxlength=\"{$maxlength}\"";
               }
               else
               {
                  $str_max_len='';
               }
               if(!isset($fld['options']['textarea'])) $fld['options']['textarea']='no';
               if(!isset($fld['options']['password'])) $fld['options']['password']='no';
               if($fld['options']['textarea']=='yes')
               {
                 $tor.="<div class=label>{$fld['label']}</div><div class=big>{$fld['before']}<textarea wrap=off name=\"{$fld['form_element_name']}\" id=\"{$fld['form_element_name']}\" cols=50 rows=5>{$fld['form_element_value']}</textarea>{$fld['after']}</div><div class=comments>{$fld['comments']}</div>\n";
               }
               elseif($fld['options']['password']=='yes')
               {
                 $tor.="<div class=label>{$fld['label']}</div><div class=big>{$fld['before']}<input type=password name=\"{$fld['form_element_name']}\" id=\"{$fld['form_element_name']}\" value=\"{$fld['form_element_value']}\" $str_max_len>{$fld['after']}</div><div class=comments>{$fld['comments']}</div>\n";
               }
               else
               {
                 $tor.="<div class=label>{$fld['label']}</div><div class=big>{$fld['before']}<input type=text name=\"{$fld['form_element_name']}\" id=\"{$fld['form_element_name']}\" value=\"{$fld['form_element_value']}\" $str_max_len >{$fld['after']}</div><div class=comments>{$fld['comments']}</div>\n";
               }
            break;
          }
        }

     }
     // ------------------------- form -- end ----------------------------------
     $tor.="
     <div>
     <input type=submit value=\"{$text['Save_Changes']}\">
     <input type=reset value=\"{$text['Reset']}\">
     </div>\n";
     $tor.="</form>\n\n\n\n\n\n\n";
     return $tor;
  }



}
?>