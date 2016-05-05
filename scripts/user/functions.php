<?php
/*
  Functions used to manage users
 
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
//
////------------------ check surf leader -- begin --------------------------------
///*
//  Check if the user is a SURF LEADER.
//	1) calculate sum of points for personal referral 
//     and compare it with pre-defined number. 
//  2) if condition is satisfied  
//  3) if the referer is a PLATINUM member assign SURF LEADER status to current user 
//  4) if the referer is not platinum member send email 
//     "You have met the point requirement to become surf leader"
//      Check if affiliate meets point requirement for Surf Leader if so email 
//      them and tell them they met the point requirement and should upgrade to
//      platinum this  month so that this month they can be a surf leader
//*/
//function check_surf_leader($affiliate_id)
//{
//   global  $db;
//   //------------------ check $affiliate_id -- begin ---------------------------
//      $a_id=checkInt($affiliate_id);
//      $affiliate_info=\e::db_getonerow("SELECT * FROM <<tp>>affiliate WHERE id=$a_id");
//      $a_id=$affiliate_info['id']=checkInt($affiliate_info['id']);
//      if($affiliate_info['id']==0) return false;
//   //------------------ check $affiliate_id -- end -----------------------------
//
//   //--------- calculate sum of points for personal referral -- begin ----------
//      $n_points="SELECT sum(at.n_points) AS n_points 
//                 FROM <<tp>>affiliate AS af, <<tp>>account_type AS at
//                 WHERE af.refered_by=$a_id AND af.account_type_id=at.id";
//      //prn($n_points);
//      $n_points=\e::db_getonerow($n_points);
//      $n_points=$n_points['n_points'];
//      //prn($n_points);
//   //--------- calculate sum of points for personal referral -- end ------------
//
//
//   //---------- and compare it with pre-defined number -- begin ----------------
//     $enough_points = ($n_points>=$GLOBALS['site_settings']['surf_leader_points']);
//     if(!$enough_points)
//     {
//        if($affiliate_info['is_surf_leader']==1)
//        {
//           // remove surf leader privileges
//              \e::db_execute("UPDATE <<tp>>affiliate SET is_surf_leader=0 WHERE id={$affiliate_info['id']}");
//           // notify user that surf leader privileges are removed
//              notify( "id:{$affiliate_info['id']}" ,join('',file("{$GLOBALS['dir_scripts']}/user/you_cannot_be_surf_leader.txt")));
//        }
//        return false;
//     }
//   //---------- and compare it with pre-defined number -- end ------------------
//
//   //---------- check for account type -- begin --------------------------------
//     if(surf_leader_account_type_id==$affiliate_info['account_type_id'])
//     {
//        if($affiliate_info['is_surf_leader']==0)
//        {
//           // assign surf leader privileges
//              \e::db_execute("UPDATE <<tp>>affiliate SET is_surf_leader=1 WHERE id={$affiliate_info['id']}");
//           // send notification "you_can_become_surf_leader"
//              notify( "id:{$affiliate_info['id']}",join('',file("{$GLOBALS['dir_scripts']}/user/you_have_become_surf_leader.txt")) );
//        }
//        return true;
//     }
//     else
//     {
//        if($affiliate_info['is_surf_leader']==0)
//        {
//           // send notification "you_can_become_surf_leader"
//              notify( "id:{$affiliate_info['id']}" ,join('',file("{$GLOBALS['dir_scripts']}/user/you_can_become_surf_leader.txt")));
//        }
//        else
//        {
//           // remove surf leader privileges
//              \e::db_execute("UPDATE <<tp>>affiliate SET is_surf_leader=0 WHERE id={$affiliate_info['id']}");
//           // notify user that surf leader privileges are removed
//              notify( "id:{$affiliate_info['id']}" ,join('',file("{$GLOBALS['dir_scripts']}/user/you_cannot_be_surf_leader.txt")) );
//        }
//        return false;
//     }
//   //---------- check for account type -- end ----------------------------------
//
//}
////------------------ check surf leader -- end ----------------------------------


?>