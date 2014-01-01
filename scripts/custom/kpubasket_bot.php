<?php
/* Arguments are:
 * view - type of view
 * site_id
 * file - source file
 */

# -------------------- set interface language - begin ---------------------------
  $debug=false;
  if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
  // $lang=$input_vars['lang'];
  $lang = get_language('lang');
# -------------------- set interface language - end -----------------------------

# -------------------------- load messages - begin -----------------------------
  global $txt;
  $txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------



# ------------------- get site info - begin ------------------------------------
  run('site/menu');
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------

# --------------------------- get site template - begin ------------------------
  $custom_page_template = sites_root.'/'.$this_site_info['dir'].'/template_index.html';
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
# --------------------------- get site template - end --------------------------








if(!isset($input_vars['view'])) return '';
//echo '<hr><pre>'; print_r($input_vars); echo '</pre><hr>';


# -------------- parse file - begin --------------------------------------------
  $file_path=realpath($custom_page_template = sites_root.'/'.$this_site_info['dir'].'/'.$input_vars['file']);

# check file path
  if( strlen(dirname($file_path)) < strlen(realpath(sites_root.'/'.$this_site_info['dir']))) die('File_not_found');

$data=file($file_path);


$parsed_data=Array('title'=>'unknown','komandà'=>Array(),'tur'=>Array());
$cnt=count($data);
for($i=0;$i<$cnt;$i++)
{
    $data[$i]=ereg_replace('#.*$','',trim($data[$i]));
    $data[$i]=ereg_replace(' +',' ',$data[$i]);
    if(strlen($data[$i])==0) continue;
    # get command and arguments
      $tmp=explode(' ',$data[$i],2);
      //echo '<hr><pre>'; print_r($tmp); echo '</pre><hr>';
    # process command
      switch($tmp[0])
      {
          case 'turnir':
              $parsed_data['title']=$tmp[1];
          break;
          case 'komandà':
              $tmp[1]=explode('=',$tmp[1]);
              $parsed_data['komandà'][$tmp[1][0]]=Array(
                       'title'=>$tmp[1][1]
                      ,'stats'=>Array(
                          'È'=>0
                         ,'Â'=>0
                         ,'Ï'=>0
                         ,'Î'=>0
                         ,'+'=>0
                         ,'-'=>0
                       )
                  );
          break;
          case 'tur':
              $current_tur=count($parsed_data['tur']);
              $parsed_data['tur'][$current_tur]=Array('title'=>$tmp[1],'igra'=>Array());
          break;
          case 'igra':
              if(!isset($current_tur))
              {
                $current_tur=count($parsed_data['tur']);
                $parsed_data['tur'][$current_tur]=Array('title'=>'Untitled tour','igra'=>Array());
              }
              $tmp[1]=explode(' ',$tmp[1]);

              $data_igry=$tmp[1][0];
              if(ereg('([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})',$data_igry,$regs))
              {
                 //echo '<hr><pre>'; print_r($regs); echo '</pre><hr>';
                 $data_igry="{$regs[3]}-{$regs[2]}-{$regs[1]}";
              }
              else echo "<b style='color:red;'>Invalid date in line #$i </b><br>";
              
              if(isset($tmp[1][1])>0 && !isset($parsed_data['komandà'][$tmp[1][1]])) echo "<b style='color:red;'>Invalid team1 in line #$i : {$data[$i]}</b><br>";
              if(isset($tmp[1][2])>0 && !isset($parsed_data['komandà'][$tmp[1][2]])) echo "<b style='color:red;'>Invalid team2 in line #$i : {$data[$i]}</b><br>";

              $parsed_data['tur'][$current_tur]['igra'][]=Array(
                  'data_igry'=> $data_igry
                 ,'komanda1' => (isset($tmp[1][1])?$tmp[1][1]:'')
                 ,'komanda2' => (isset($tmp[1][2])?$tmp[1][2]:'')
                 ,'schet'    => (isset($tmp[1][3])?$tmp[1][3]:'')
                 ,'urlstats' => (isset($tmp[1][4])?$tmp[1][4]:'')
              );
          break;
      }


}
// echo '<hr><pre>'; print_r($parsed_data); echo '</pre><hr>';
# -------------- parse file - end ----------------------------------------------

# -------------- draw - begin --------------------------------------------------
  $show='';
  $input_vars['view']=explode(',',$input_vars['view']);
  //echo '<hr><pre>'; print_r($parameters['view']); echo '</pre><hr>';
  foreach($input_vars['view'] as $view)
  {
      switch($view)
      {
          case 'next_match':
              # -------------- search for date of next match - begin -----------
              $current_date=date('Y-m-d');
              //echo '<hr><pre>'; print_r($current_date); echo '</pre><hr>';
              $next_tour=0;
              $next_match=0;
              $dt='0000-00-00';
              foreach($parsed_data['tur'] as $t=>$tur)
              {
                  foreach($tur['igra'] as $i=>$igra)
                  {
                      if(isset($input_vars['team']) && $igra['komanda1']!=$input_vars['team'] && $igra['komanda2']!=$input_vars['team']) continue;
                      if(strcmp($igra['data_igry'],$current_date)>0 && strcmp('0000-00-00',$dt)==0)
                      {
                          $dt=$igra['data_igry'];
                          $next_tour=$t;
                          $next_match=$i;
                      }elseif(strcmp($igra['data_igry'],$current_date)>0 && strcmp($igra['data_igry'],$dt)<0)
                      {
                          $dt=$igra['data_igry'];
                          $next_tour=$t;
                          $next_match=$i;
                      }
                  }
              }
              # -------------- search for date of next match - end -------------
              # -------------- show next matches - begin -----------------------
              $show.="<p><b>Ñëåäóşùèå èãğû:</b>";
              foreach($parsed_data['tur'] as $t=>$tur)
              {
                  foreach($tur['igra'] as $i=>$igra)
                  {
                      if(isset($input_vars['team']) && $igra['komanda1']!=$input_vars['team'] && $igra['komanda2']!=$input_vars['team']) continue;
                      if(strcmp($igra['data_igry'],$dt)==0)
                      {
                          $cmd1=$igra['komanda1'];
                          $cmd2=$igra['komanda2'];
                          $show.="<div>
                              {$tur['title']};
                              ".date('d.m.Y',strtotime($dt)).";
                              {$parsed_data['komandà'][$cmd1]['title']}
                              -
                              {$parsed_data['komandà'][$cmd2]['title']}
                             </div>";
                      }
                  }
              }
              $show.="</p>";
              # -------------- show next matches - end -------------------------


          break;


          case 'last_match':
              # -------------- search last matches - begin ---------------------
              $current_date=date('Y-m-d');
              $last_tour=0;
              $last_match=0;
              $dt=$parsed_data['tur'][$last_tour]['igra'][$last_match]['data_igry'];
              foreach($parsed_data['tur'] as $t=>$tur)
              {
                  foreach($tur['igra'] as $i=>$igra)
                  {
                      if(isset($input_vars['team']) && $igra['komanda1']!=$input_vars['team'] && $igra['komanda2']!=$input_vars['team']) continue;
                      if(strcmp($igra['data_igry'],$current_date)<=0 && strcmp($current_date,$dt)<0)
                      {
                          $dt=$igra['data_igry'];
                          $last_tour=$t;
                          $last_match=$i;
                      }elseif(strcmp($igra['data_igry'],$current_date)<=0 && strcmp($igra['data_igry'],$dt)>0)
                      {
                          $dt=$igra['data_igry'];
                          $last_tour=$t;
                          $last_match=$i;
                      }
                  }
              }
              # -------------- search last matches - begin ---------------------
              # -------------- show last matches - begin -----------------------
              $show.="<p><b>Ïîñëåäíèå èãğû:</b>";
              foreach($parsed_data['tur'] as $t=>$tur)
              {
                  foreach($tur['igra'] as $i=>$igra)
                  {
                      if(isset($input_vars['team']) && $igra['komanda1']!=$input_vars['team'] && $igra['komanda2']!=$input_vars['team']) continue;
                      if(strcmp($igra['data_igry'],$dt)==0)
                      {
                         if(strlen($igra['urlstats'])>0) $igra['urlstats']="(<a href=\"{$igra['urlstats']}\">îò÷åò</a>)";

                         $show.="<div>
                              {$tur['title']};
                              ".date('d.m.Y',strtotime($dt)).";
                              {$parsed_data['komandà'][$igra['komanda1']]['title']}
                              -
                              {$parsed_data['komandà'][$igra['komanda2']]['title']};
                              {$igra['schet']} {$igra['urlstats']}
                             </div>";
                      }
                  }
              }
              $show.="</p>";
              # -------------- show last matches - end -------------------------
          break;

          case 'matches':
              # -------------- show all matches - begin ------------------------
              $show.="<h2>Êàëåíäàğü âñòğå÷</h2>
              <style>
                .matchestable td{border:1px dotted gray; font-size:10pt; vertical-align:top;}
              </style>
              <table class=matchestable>";
              foreach($parsed_data['tur'] as $t=>$tur)
              {
                  $show.="<tr><td colspan=4><h3>{$tur['title']}</h3></td></tr>";
                  foreach($tur['igra'] as $i=>$igra)
                  {
                      if(isset($input_vars['team']) && $igra['komanda1']!=$input_vars['team'] && $igra['komanda2']!=$input_vars['team']) continue;
                      if(strlen($igra['urlstats'])>0) $igra['urlstats']="(<a href=\"{$igra['urlstats']}\">îò÷åò</a>)";

                      $show.="<tr>
                              <td>".date('d.m.Y',strtotime($igra['data_igry']))."</td>
                              <td>".(strlen($igra['komanda1'])>0?$parsed_data['komandà'][$igra['komanda1']]['title']:'')."</td>
                              <td><nobr>{$igra['schet']} {$igra['urlstats']}</nobr></td>
                              <td>".(strlen($igra['komanda2'])>0?$parsed_data['komandà'][$igra['komanda2']]['title']:'')."</td>
                              </tr>";
                      //
                  }
              }
              $show.="</table>";
              # -------------- show all matches - end --------------------------

          break;

          case 'table':
              # -------------- collect stats - begin ---------------------------
              foreach($parsed_data['tur'] as $t=>$tur)
              {
                  foreach($tur['igra'] as $i=>$igra)
                  {
                      if(strlen($igra['schet'])==0) continue;
                      $schet=explode(':',$igra['schet']);

                      $parsed_data['komandà'][$igra['komanda1']]['stats']['È']++;
                      $parsed_data['komandà'][$igra['komanda2']]['stats']['È']++;

                      $parsed_data['komandà'][$igra['komanda1']]['stats']['+']+=$schet[0];
                      $parsed_data['komandà'][$igra['komanda1']]['stats']['-']+=$schet[1];

                      $parsed_data['komandà'][$igra['komanda2']]['stats']['-']+=$schet[0];
                      $parsed_data['komandà'][$igra['komanda2']]['stats']['+']+=$schet[1];

                      if($schet[0]>$schet[1])
                      {
                         $parsed_data['komandà'][$igra['komanda1']]['stats']['Â']++;
                         $parsed_data['komandà'][$igra['komanda1']]['stats']['Î']+=2;

                         $parsed_data['komandà'][$igra['komanda2']]['stats']['Ï']++;
                         $parsed_data['komandà'][$igra['komanda2']]['stats']['Î']+=1;
                      }
                      else
                      {
                         $parsed_data['komandà'][$igra['komanda1']]['stats']['Ï']++;
                         $parsed_data['komandà'][$igra['komanda1']]['stats']['Î']+=1;

                         $parsed_data['komandà'][$igra['komanda2']]['stats']['Â']++;
                         $parsed_data['komandà'][$igra['komanda2']]['stats']['Î']+=2;
                      }

                  }
              }
              function cmp($a, $b) 
              {
                if ($a['stats']['Î'] == $b['stats']['Î'])
                {
                    $apm=$a['stats']['+']-$a['stats']['-'];
                    $bpm=$b['stats']['+']-$b['stats']['-'];
                    if($apm == $bpm) return 0;
                    return ($apm < $bpm) ? 1 : -1;
                }
                return ($a['stats']['Î'] < $b['stats']['Î']) ? 1 : -1;
              }
              $turtable=$parsed_data['komandà'];
              uasort($turtable, "cmp");
              
              $show.="<h2>Òóğíèğíàÿ òàáëèöà</h2>
                      <style>
                      .turtable td{font-size:10pt; vertical-align:top; border:1px dotted gray;}
                      </style>
                      <table border=0px cellspacing=2px cellpadding=0px class=turtable>
                        <tr>
                        <th>¹</th>
                        <th>Êîìàíäà</th>
                        <th>È</th>
                        <th>Â</th>
                        <th>Ï</th>
                        <th>Î</th>
                        <th>Ç</th>
                        <th>Ïğ</th>
                        <th>+/-</th>
                        </tr>";
              $N=1;
              foreach($turtable as $team)
              {
                  $pm=$team['stats']['+']-$team['stats']['-'];
                  $show.="<tr>
                           <td>{$N}</td>
                           <td>{$team['title']}</td>
                           <td>{$team['stats']['È']}</td>
                           <td>{$team['stats']['Â']}</td>
                           <td>{$team['stats']['Ï']}</td>
                           <td><b>{$team['stats']['Î']}</b></td>
                           <td>{$team['stats']['+']}</td>
                           <td>{$team['stats']['-']}</td>
                           <td>{$pm}</td>
                          </tr>";
                  $N++;
              }
              $show.="</table>";
              //echo '<hr><pre>'; print_r($turtable); echo '</pre><hr>';
              # -------------- collect stats - end -----------------------------
          break;

      }

  }
# -------------- draw - end ----------------------------------------------------








  run('site/page/page_view_functions');

  # get site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);


# -------------------- get list of page languages - begin ----------------------
    $tmp=db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM {$table_prefix}ec_item  AS ec_item
                     WHERE ec_item.site_id={$site_id}
                       AND ec_item.ec_item_cense_level&".ec_item_show."");
    $existing_languages=Array();
    foreach($tmp as $tm) $existing_languages[$tm['lang']]=$tm['lang'];
    // prn($existing_languages);


    $lang_list=list_of_languages();
    $cnt=count($lang_list);
    for($i=0;$i<$cnt;$i++)
    {
        if(!isset($existing_languages[$lang_list[$i]['name']]))
        {
          unset($lang_list[$i]);
          continue;
        }
        $lang_list[$i]['url']=$lang_list[$i]['href'];
        $lang_list[$i]['lang']=$lang_list[$i]['name'];
    }
    $lang_list=array_values($lang_list);
    //prn($lang_list);
# -------------------- get list of page languages - end ------------------------





  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$parsed_data['title']
                                               ,'content'=>$show
                                               ,'abstract'=> ''
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name; $main_template_name='';
?>