var linkId=0;
var tinymce_settings={
        // Location of TinyMCE script
        script_url : './scripts/lib/tiny_mce/tiny_mce.js',

        // General options
        relative_urls : false,
        convert_urls : false,
        theme : "advanced",
        plugins : "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

        // Theme options
        //theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
        //theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        theme_advanced_buttons2 : "cut,copy,paste,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
        //theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
        theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,pagebreak",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true //,
//        // Drop lists for link/image/media/template dialogs
//        template_external_list_url : "js/template_list.js",
//        external_link_list_url : "js/link_list.js",
//        external_image_list_url : "js/image_list.js",
//        media_external_list_url : "js/media_list.js",

    };



function tinymce_init(selector, settings){
    for(var p in settings){
        tinymce_settings[p]=settings[p];
        //console.log(p+'='+settings[p]);
    }

    $(selector).each(function(index,element){
        linkId+=3;
        var e=$(element);

        var textareaId=e.attr('id');
        if(!textareaId){
            textareaId="textarea"+linkId;
            e.attr('id',textareaId);
        }
        // console.log($('#'+textareaId));
        // console.log(e);
        //e.before('<div id=\"initWYSIWYG'+(textareaId)+'\"><a href="javascript:void(0)" class=\"modeSwitch\" onclick=\"tinymce_start(\''+textareaId+'\')\">WYSIWYG</a><span class=\"modeSwitch activeMode\">HTML</span></div>');
        // console.log(tinymce_settings);
        $('#'+textareaId).tinymce(tinymce_settings);
        /////tinymce_start(textareaId);
    });
}




function insert_link(text,url){
    var html="<a href=\""+url+"\">"+text+"</a>";
    tinyMCE.execCommand('mceInsertContent',false,html);
}

function insert_html(text){
    tinyMCE.execCommand('mceInsertContent',false,text);
}


function insert_image(url){
    tinyMCE.execCommand('mceInsertContent',false,'<img src="'+url+'"/>');
}









function tinymce_start(textareaId) {
    $('#initWYSIWYG'+textareaId).remove();
    $('#'+textareaId)
    .each(function(index,element){
        linkId+=3;
        var e=$(element);
        var elementId=e.attr('id');
        if(!elementId){
            elementId="textarea"+linkId;
            e.attr('id',elementId);
        }
        //e.before('<div><a href="javascript:void(0)" class=\"modeSwitch activeMode\" id=\"modeSwitch'+(linkId+1)+'\" onclick=\"toWYSIWYG(\''+elementId+'\',\'modeSwitch'+(linkId+1)+'\')\">WYSIWYG</a> <a href="javascript:void(0)"  id=\"modeSwitch'+(linkId+2)+'\" class=\"modeSwitch\" onclick=\"toHTMLSource(\''+elementId+'\',\'modeSwitch'+(linkId+2)+'\')\">HTML Source</a></div>')
        e.before('<div id=\"initHTML'+(textareaId)+'\"><span class=\"modeSwitch activeMode\">WYSIWYG</span> <a href="javascript:void(0)" class=\"modeSwitch\" onclick=\"toHTMLSource(\''+elementId+'\')\">HTML</a></div>');
    })
    .tinymce(tinymce_settings);

}


function toHTMLSource(textareaId){
    $('#initHTML'+textareaId).remove();
    $('#'+textareaId)
    .before('<div id=\"initWYSIWYG'+(textareaId)+'\"><a href="javascript:void(0)" class=\"modeSwitch\" onclick=\"tinymce_start(\''+textareaId+'\')\">WYSIWYG</a> <span class=\"modeSwitch activeMode\">HTML</span></div>')
    .tinymce().remove();
}

document.write("<style type=\"text/css\">\n.modeSwitch{display:inline-block;padding:3pt;color:blue;}\n.activeMode{background-color:yellow;}\n\n</style>");