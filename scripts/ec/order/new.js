/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */




var request = false;
function request_user_info()
{
    // Получить данные из web-формы
       var ec_user_email = document.getElementById("ec_user_email_login").value;
       var ec_user_password = document.getElementById("ec_user_password_login").value;

    // Продолжать только если есть значения обоих полей
       if ((ec_user_email == null) || (ec_user_email == "")) return;
       if ((ec_user_password == null) || (ec_user_password == "")) return;


    // Создать URL для подключения
       var url = "index.php";

    // составить данные
       var data = "action=site_visitor/ajax_login&site_visitor_email="+escape(ec_user_email)+"&site_visitor_password="+escape(ec_user_password);
       //alert(url+"\n"+data);

    request=ajax_load(url,
              data,
              function (){
                   if (request.readyState == 4) {
                         var response = request.responseText;
                         var block=document.getElementById("ec_user_saved_info");
                         
                         block.style.display='block';
                         if(response.match(/<!-- No_saved_info -->/))
                         {
                            block.innerHTML = response;
                         }
                         else
                         {
                            block.innerHTML = draw_response(response);
                            document.getElementById("ec_user_login_form").style.display='none';
                            document.getElementById("ec_user_logout_form").style.display='block';
                         }
                   }
              });
}
function draw_response(response)
{
    eval(" var user="+response);
    str="";
    for(var i in user['data'])
    {
        ud=user['data'][i];
        str+="<p style='border:1px dotted gray;'>\n";
        str+="<table>\n";
        str+="<tr>\n";
        str+="<td rowspan='8'><input type=button value='<<' onclick=\"set_values('"+ud['ec_user_id']+"')\"></td>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_email'))\"><span id='ec_user_email"+ud['ec_user_id']+"'>"+ud['site_visitor_email']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_name'))\"><span id='ec_user_name"+ud['ec_user_id']+"'>"+ud['ec_user_name']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_telephone'))\"><span id='ec_user_telephone"+ud['ec_user_id']+"'>"+ud['ec_user_telephone']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_icq'))\"><span id='ec_user_icq"+ud['ec_user_id']+"'>"+ud['ec_user_icq']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_delivery_city'))\"><span id='ec_user_delivery_city"+ud['ec_user_id']+"'>"+ud['ec_user_delivery_city']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_delivery_region'))\"><span id='ec_user_delivery_region"+ud['ec_user_id']+"'>"+ud['ec_user_delivery_region']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_delivery_street_address'))\"><span id='ec_user_delivery_street_address"+ud['ec_user_id']+"'>"+ud['ec_user_delivery_street_address']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+=" <tr>\n";
        str+="   <td><a href=\"javascript:void(set_one_value("+ud['ec_user_id']+",'ec_user_delivery_suburb'))\"><span id='ec_user_delivery_suburb"+ud['ec_user_id']+"'>"+ud['ec_user_delivery_suburb']+"</span></a></td>\n";
        str+=" </tr>\n";
        str+="</table>\n";
        str+="</p>\n\n\n";
    }
    return str;
}


var reques2 = false;
function do_logout()
{
   var url = "index.php";
   var data = "action=site_visitor/ajax_logout";
   reques2 = ajax_load(url,
                       data,
                       function (){
                           if (reques2.readyState == 4) {
                               var response = reques2.responseText;
                               //alert(response);
                               var block=document.getElementById("ec_user_saved_info");
                               block.innerHTML = '';
                               block.style.display='none';
                               document.getElementById("ec_user_login_form").style.display='block';
                               document.getElementById("ec_user_logout_form").style.display='none';
                           }
                       });
}

function set_values(id){
    set_one_value(id,'ec_user_name');
    set_one_value(id,'ec_user_email');
    set_one_value(id,'ec_user_telephone');
    set_one_value(id,'ec_user_icq');
    set_one_value(id,'ec_user_delivery_street_address');
    set_one_value(id,'ec_user_delivery_city');
    set_one_value(id,'ec_user_delivery_suburb');
    set_one_value(id,'ec_user_delivery_region');
}
function set_one_value(id,name){
    document.getElementById(name).value=document.getElementById(name+id).innerHTML;
}
function submit_if_needed(ev)
{
    //alert(ev.which);
    if(ev.which==13)
    {
       request_user_info();
    }
    return false;
}

