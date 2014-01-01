<?
/*
  Template processor
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
class gd_template
{
   var $values;          // parsed template
   var $variables;       // template variables
   var $template_dir;    // where templates are located
   var $cache_dir;       // where cache is located

   // get (sub)template   
   function get_template($tplname){return $this->values['block-'.$tplname];}
   
   // clear (sub)template   
   function clear_template($tplname){$this->values['block-'.$tplname]='';}
   
   // clear all (sub)templates   
   function clear_all_templates(){$this->values=Array();}

   // set template variables
   function set_variables($arr)
   {
      if(!is_array($this->values)) $this->values=Array();
      if(is_array($arr))
      {
        $changed=true;
        $this->values=array_merge($this->values,$arr);
        while($changed)
        {
           $changed=false;
           $cnt=array_keys($this->values);
           foreach($cnt as $key)
           {
              if(is_array($this->values[$key]))
              {
                 $changed=true;
                 foreach($this->values[$key] as $k=>$v)
                    $this->values["{$key}:{$k}"]=$v;
                 unset($this->values[$key]);
              }
           }
        }
        
      }
   }


   //
   // load template from file
   // $template is template file path
   //
   function load_template_file($template)
   {
      global $GroupRoot,$fileprefix;
      $path_found='';
      $path_0=$this->template_dir."/{$template}";
      $path_1=$this->template_dir."/{$template}.html";

      $this->variables=Array();
      $this->values=Array();

      if(file_exists($path_1)) $path_found=$path_1;
      else
      {
         if(file_exists($path_0)) $path_found=$path_0;
      }
      
      if(strlen($path_found)>0)
      {
        //prn($path_found);
        $cached_file_path=$this->cache_dir.'/'.rawurlencode(str_replace($this->template_dir,'',$path_found));
        //prn($cached_file_path);
        
        $refresh_cache=true;
        if(file_exists($cached_file_path))
           if(filemtime($cached_file_path)>=filemtime($path_found))
              $refresh_cache=false;

        //prn($this->variables);
        //$refresh_cache=true;
        if($refresh_cache)
        {
           //prn('refreshing template '.$path_found);
           $this->set_template(join('',file($path_found)));
           $fp=fopen($cached_file_path,'w');
           if($fp)
           {
              fwrite( $fp,base64_encode(serialize($this->variables))."\n".base64_encode(serialize($this->values)));
              fclose($fp);
           }
        }
        else
        {
            $cached_value=file($cached_file_path);
            $this->variables=unserialize(base64_decode($cached_value[0]));
            $this->values   =unserialize(base64_decode($cached_value[1]));
        }
        //prn($this->variables);
      }
      else $this->set_template("Template {$template} not found");
      
   }




   //
   // load template from string and parse it
   // $template is string containing template
   //
   function set_template($template)
   {
      //------------------------- constants -- begin ---------------------------
        $block_start_tag='<%begin_block:';
        $block_start_tag_len=strlen($block_start_tag);
        $end_block_tag='<%end_block:';
        $end_block_tag_len=strlen($end_block_tag);
        $close_tag='%>';
        $close_tag_len=strlen($close_tag);
      //------------------------- constants -- end -----------------------------


      $this->variables=Array('block-main'=>$template);
      $this->values=Array();
      $is_changed=true;
      while($is_changed)
      {
        $is_changed=false;
        $keys=array_keys($this->variables);
        foreach($keys as $key)
        {
           $tpl=& $this->variables[$key];

           $end_block_pos=0;

           global $debug;

           $begin_block_pos=strpos($tpl,$block_start_tag);
           if($debug) prn("start_block : ".$begin_block_pos);
           // ---------- get sub-templates -- begin ----------------------------
           while(!($begin_block_pos===false))
           {
              $block_name_start =$begin_block_pos+$block_start_tag_len;
              $block_name_finish=strpos($tpl,$close_tag,$block_name_start);
              $block_name=substr($tpl,$block_name_start,$block_name_finish-$block_name_start);

              $inner_block_begin=$begin_block_pos+strlen($block_start_tag.$block_name.$close_tag);
              $inner_block_end=strpos($tpl,$end_block_tag.$block_name.$close_tag,$begin_block_pos);

              $end_block_pos=$inner_block_end+strlen($end_block_tag.$block_name.$close_tag);

              $this->variables['block-'.$block_name]=substr($tpl,$inner_block_begin,$inner_block_end-$inner_block_begin);
              
              if($debug) prn(checkStr($tpl));
              $tpl=substr($tpl,0,$begin_block_pos)."<%block-{$block_name}%>".substr($tpl,$end_block_pos);
              $is_changed=true;

              $begin_block_pos=strpos($tpl,$block_start_tag);
              if($debug) prn("start_block : ".$begin_block_pos);
           }
           // ---------- get sub-templates -- end ------------------------------
        }
      }

      //prn($this->variables);
   }




   //
   // process template block
   // $block_name is name of the block
   //
   function process_template($block_name)
   {
      if(isset($this->variables["block-{$block_name}"]))
      {
         preg_match_all('/<%([^>]*)%>/i',$this->variables["block-{$block_name}"],$matches);
         
         $search=Array();
         $replace=Array();
         foreach($matches[0] as $key=>$val)
         {
            $search[]='/'.$val.'/i';
            $replace[]=$this->values[$matches[1][$key]];
         }
         $this->values["block-{$block_name}"].=preg_replace($search,$replace,$this->variables["block-{$block_name}"]);
         return $this->values["block-{$block_name}"];
      }
   }
}


/*

$ddd=new gd_template;
$ddd->set_template("

<%begin_block:var1%>
<i><p><b><%var2%></b>
<b><%var3%></b></p></i>
<%end_block:var1%>


<%begin_block:var2%>
<p><font color=\"red\"><b><%var2%>
<%var3:f1%></b> <%var3:f2%> </font></p>
 <%begin_block:var3%>
<i><p><font color=\"green\"> <%var2%>
 <%var3%></font></p></i>
 <%end_block:var3%>
<%end_block:var2%>


");

$ddd->set_variables(Array('var2'=>'var2','var3'=>Array('f1'=>'F1','f2'=>'F2')));
echo '<hr><pre>';print_r($ddd);echo '</pre><hr>';
$ddd->process_template('var1');
$ddd->process_template('var1');
$ddd->process_template('var1');

$ddd->process_template('var3');
$ddd->process_template('var3');
$ddd->process_template('var3');

$ddd->process_template('var2');
$ddd->process_template('var2');

echo '<hr>'.$ddd->process_template('main').'<hr>';
  */

?>