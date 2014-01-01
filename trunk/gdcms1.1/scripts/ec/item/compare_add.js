var request;


function compare_add(ec_item_id)
{
    //alert('index.php?action=ec/item/compare_add&ec_item_id='+ec_item_id);
    request=ajax_load(
              'index.php?action=ec/item/compare_add&ec_item_id='+ec_item_id,
              '',
              function (){
                   if (request.readyState == 4) {
                         var response = request.responseText;
                         //alert(response);
                         var block=document.getElementById("compare_add_"+ec_item_id);
                         block.innerHTML='<!-- '+response+' -->';
                         //alert(response);
                   }
              });
}
