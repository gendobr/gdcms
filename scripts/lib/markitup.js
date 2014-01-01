function insert_link(text,url){
    $.markItUp({ replaceWith:'<a href="'+url+'">'+text+'</a>' } );
}

function insert_image(url){
    $.markItUp({ replaceWith:'<img align="left" src="'+url+'"/>' } );
}

function insert_html(html){
    $.markItUp({ replaceWith:html } );
}