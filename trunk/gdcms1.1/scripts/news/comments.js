function reply_to(comment_id){
    // set news_comment_parent_id value
    document.getElementById('news_comment_parent_id').value=comment_id;
    // move form to appropriate position
    // 'new_comment_'+comment_id
    var send_comment_form=document.getElementById('send_comment_form');
    var new_comment=document.getElementById('new_comment_'+comment_id);
    // get parent of the send_comment_form
    var parentNode = send_comment_form.parentNode;
    // remove send_comment_form from old place
    parentNode.removeChild(send_comment_form);
    // insert send_comment_form at new place
    new_comment.appendChild(send_comment_form);

    var news_comment_link=document.getElementById('news_comment_link');
    if(comment_id>0){
        news_comment_link.style.display='block';
    }else{
        news_comment_link.style.display='none';
    }
}


function comment_login_popup(url)
{
  var width  = Math.round(screen.availWidth*0.5);
  var height = Math.round(screen.availHeight*0.5);
  var DemoWindow, left=0, top=0;
  if (document.all || document.layers) {
    left = Math.round((screen.availWidth-width)/2);
    top  = Math.round((screen.availHeight-height)/2);
  }
  DemoWindow=window.open(url,"comment_login_popup","height="+height+",width="+width+",scrollbars=1,resizable=1,menubar=0,status=1,left="+left+",top="+top+",screenX="+left+",screenY="+top);
  if (DemoWindow) {
   DemoWindow.focus();
   DemoWindow.resizeTo(width,height);
   DemoWindow.moveTo(left,top);
  }
  return DemoWindow;
}