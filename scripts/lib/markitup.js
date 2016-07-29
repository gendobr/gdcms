function insert_link(text,url, attributes){
    var attrHtml="";
    if(attributes){
        for(var aN in attributes){
            attrHtml+=" "+aN+"=\""+attributes[aN]+"\" ";
        }
    }
    var ret = $.markItUp({ replaceWith:'<a href="'+url+'" '+attrHtml+'>'+text+'</a>' } ); 
}

function insert_image(url){
    $.markItUp({ replaceWith:'<img align="left" src="'+url+'"/>' } );
}

function insert_html(html){
    $.markItUp({ replaceWith:html } );
}