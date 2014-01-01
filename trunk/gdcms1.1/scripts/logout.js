/*
 * do ajax logout
 */

var dologout=function(){
$.ajax({ url: "index.php?action=logout&t="+Math.random(),
         contentType: "application/x-www-form-urlencoded;charset=windows-1251",
         success: function(){
                  window.location.href='index.php';
         }});
};
