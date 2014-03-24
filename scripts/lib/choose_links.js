function init_links(selector){
    $('body').append("<style>\n#file_links_closer{display:inline-block;padding:2px;background-color:red;border:1px solid yellow;cursor:default;text-decoration:none;} #file_links_dragger{cursor:move;text-align:right;}\n#file_links_container{position:absolute; display:block; padding:3px 3px 3px 3px; background-color:silver;color:black;} #file_links_dragger{background-color:blue;} #file_links{width:250px; height:300px; overflow:auto;}\n</style><div id=\"file_links_container\"><div id=\"file_links_dragger\"><a id=\"file_links_closer\">&times;</a></div><div id=\"file_links\">Test</div></div>");
    $('#file_links_container').draggable().hide();
    $('#file_links_dragger').mousedown(function(){
        $('#file_links_container').draggable("enable");
    });
    $('#file_links_dragger').mouseup(function(){
        $('#file_links_container').draggable("disable").removeClass('ui-draggable-disabled ui-state-disabled');
    });
    $('#file_links_closer').click(function(){
        $('#file_links_container').hide();
    });
    $('body').append("<script type='text/javascript' src='./scripts/lib/jquery.form.js'></script>");
}
function show_file_links(datasource){
    var offset = $(this).offset();
    $('#file_links_container').show();
    $('#file_links').empty().offset(offset);
    $.ajax({
        url: datasource,
        success: draw_file_links,
        dataType: 'json'
    });

}
function draw_file_links(json){
    var html='';

    if(json.parents){
        for(var i in json.parents){
            var parent=json.parents[i];
            //console.log(f);
            if(parent.name==''){
                html+="/<a href='javascript:void(show_file_links(\""+parent.url+"\"))'>home</a>"
            }else{
                html+="/<a href='javascript:void(show_file_links(\""+parent.url+"\"))'>"+parent.name+"</a>"
            }
        }
    }

    if(json.dirs){
        for(var i in json.dirs){
            var f=json.dirs[i];
            html+="<div><img src='img/icon_dir.png'><a href='javascript:void(show_file_links(\""+f.url+"\"))'>"+f.name+"</a></div>"
        }
    }
    if(json.files){
        var image_extension=/\.(jpg|jpeg|png|gif)$/i;
        for(var i in json.files){
            var f=json.files[i];

            if(!f.prefix){
                f.prefix='';
            }
            if(image_extension.test(f.name)){
                html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0)' onclick='insert_image(\""+f.url+"\")'>"+f.name+"<br><img src='"+f.url+"' width=100px height=100px alt='"+f.name+"'></a></nobr></div>"
            }else{
                html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0)' onclick='insert_link(\""+s(f.name)+"\",\""+f.url+"\")'>"+f.name+"</a></nobr></div>"
            }

        }
    }

    // draw upload form
    var site_id=$('input[name="site_id"]').attr('value');
    // console.log('site_id='+site_id);
    var dirname='';
    for(var i=0;i<json.parents.length;i++){
        if(i==0){
            dirname='';
        }else{
            dirname+='/'+json.parents[i].name;
        }
    }
    //console.log('dirname='+dirname);

    html+="<form id='site_upload_receiver' action=\"index.php?action=site/upload_receiver&site_id="+site_id+"&current_dir="+dirname+"\"  enctype=\"multipart/form-data\" method=\"post\">\n";
    html+="<hr>Upload:<br><input type=file name=file style='width:120px;' class=\"multi\"><br><input type=submit value=Upload style='font-size:9pt;'>";
    html+="</form>";
    html+="<script type='text/javascript'>$('#site_upload_receiver').ajaxForm(function() { show_file_links('"+json.datasource+"');  }); </script>"
    html+="<form id='file_directory_creator' action=\"index.php?action=site/file_directory_creator&site_id="+site_id+"&current_dir="+dirname+"\"  enctype=\"multipart/form-data\" method=\"post\">\n";
    html+="<hr>Create dir:<br><input type=text name=newdir style='width:150px;' class=\"multi\"><input type=submit value='Create' style='font-size:9pt;'>";
    html+="</form>";
    html+="<script type='text/javascript'>$('#file_directory_creator').ajaxForm(function() { show_file_links('"+json.datasource+"');  }); </script>"
    $('#file_links').append(html);
}

function display_file_links(datasource,element){
    show_file_links(datasource);
    var offset=$(element).offset();
    //console.log($(element).offset());
    $('#file_links_container').css({
        top:Math.round(offset.top)+'px'
    }).draggable("disable").removeClass('ui-draggable-disabled ui-state-disabled');
}

















function display_page_links(datasource,element){
    show_page_links(datasource);
    var offset=$(element).offset();
    //console.log($(element).offset());
    $('#file_links_container').css({
        top:Math.round(offset.top)+'px'
    }).draggable("disable").removeClass('ui-draggable-disabled ui-state-disabled');
}

function show_page_links(datasource){
    var offset = $(this).offset();
    $('#file_links_container').show();
    $('#file_links').empty().offset(offset);
    $.ajax({
        url: datasource,
        success: draw_page_links,
        dataType: 'json'
    });
}
function draw_page_links(json){
    var html='';
    // console.log(json);
    if(json.files){
        for(var i in json.files){
            var f=json.files[i];

            if(!f.prefix){
                f.prefix='';
            }
            html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0)' onclick='insert_link(\""+s(f.name)+"\",\""+f.url+"\")'>"+f.name+"</a></nobr></div>"
        }
    }
    $('#file_links').append(html);
}














function display_category_links(datasource,element){
    show_category_links(datasource);
    var offset=$(element).offset();
    //console.log($(element).offset());
    $('#file_links_container').css({
        top:Math.round(offset.top)+'px'
    }).draggable("disable").removeClass('ui-draggable-disabled ui-state-disabled');
}
function show_category_links(datasource){
    var offset = $(this).offset();
    $('#file_links_container').show();
    $('#file_links').empty().offset(offset);
    $.ajax({
        url: datasource,
        success: draw_category_links,
        dataType: 'json'
    });
}

function draw_category_links(json){
    var html='';

    if(json.parents){
        for(var i in json.parents){
            var parent=json.parents[i];
            //console.log(f);
            if(parent.name==''){
                html+="/<a href='javascript:void(show_file_links(\""+parent.url+"\"))'>home</a>"
            }else{
                html+="/<a href='javascript:void(show_file_links(\""+parent.url+"\"))'>"+parent.name+"</a>"
            }
        }
    }

    if(json.dirs){
        for(var i in json.dirs){
            var f=json.dirs[i];
            html+="<div><img src='img/icon_dir.png'><a href='javascript:void(show_file_links(\""+f.url+"\"))'>"+f.name+"</a></div>"
        }
    }
    if(json.files){

        for(var i in json.files){
            var f=json.files[i];

            if(!f.prefix){
                f.prefix='';
            }
            html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0)' onclick='insert_link(\""+s(f.name)+"\",\""+f.url+"\")'>"+f.name+"</a></nobr></div>"
        }
    }

    $('#file_links').append(html);
}











function display_gallery_links(datasource,element){
    show_gallery_links(datasource);
    var offset=$(element).offset();
    //console.log($(element).offset());
    $('#file_links_container').css({
        top:Math.round(offset.top)+'px'
    }).draggable("disable").removeClass('ui-draggable-disabled ui-state-disabled');
}
function show_gallery_links(datasource){
    var offset = $(this).offset();
    $('#file_links_container').show();
    $('#file_links').empty().offset(offset);
    $.ajax({
        url: datasource,
        success: draw_gallery_links,
        contentType: "application/x-www-form-urlencoded;charset=windows-1251",
        dataType: 'json'
    });
}

function draw_gallery_links(json){
    var html='';
    if(json.files){
        window.gallery_links=json;
        html='';
        for(var i in json.files){
            var f=json.files[i];
            if(!f.prefix){
                f.prefix='';
            }
            // html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0);' onclick='insert_link(\""+s(f.name)+"\",\""+f.url+"\")'>"+f.name+"</a></nobr></div>"
            //var b1=f.htmlblock;
            //var b2=b1.replace(/'/g,'&#39;');
            //var b3=b2.replace(/"/g,'&quot;');
            // console.log(b3);
            //html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0);' onclick='insert_html(\""+b3+"\")'>"+f.name+"</a></nobr></div>"
            html+="<div><nobr><img src='img/icon_file.png'>"+f.prefix+"<a href='javascript:void(0);' onclick='insert_html(window.gallery_links.files["+i+"].htmlblock)'>"+f.name+"</a></nobr></div>";
        }
    }

    $('#file_links').append(html);
}






function s(str){
    var st=str+"";
    return st.replace('"', "`");
}



