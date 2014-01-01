metaTagsButtonList = {
    'description': '<meta name="description" content="text">',
    'keywords': '<meta name="keywords" content="text">',
    "robots": '<meta name="robots" content="selection">',
    
    "abstract": '<meta name="abstract" content="text">',
    "author": '<meta name="author" content="text">',
    
    "Content-Type": '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1 ">',
    "Cache-control": '<meta http-equiv="Cache-control" content="public">',
    "content-disposition": '<meta http-equiv="content-disposition" content="description">',
    "copyright": '<meta name="copyright" content="text">',
    
    "distribution": '<meta name="distribution" content="option">',
    
    "expires": '<meta name="expires" content="date">',
    "generator": '<meta name="generator" content="program">',
    "googlebot": '<meta name="googlebot" content="noodp">',
    
    "imagetoolbar": '<meta http-equiv="imagetoolbar" content="value">',
    "language": '<meta name="language" content="english">',
    "name": '<meta http-equiv="name" content="value">',
    "no-email-collection": '<meta name="no-email-collection" content="link or terms">',
    "pragma": '<meta http-equiv="pragma" content="no-cache">',
    
    "rating": '<meta name="rating" content="value">',
    "refresh": '<meta http-equiv="refresh" content="30; ,URL=http://www.metatags.info/login">',
    "reply-to": '<meta name="reply-to" content="email address">',
    "Resource-Type": '<meta http-equiv="Resource-Type" content="document">',
    "revisit-after": '<meta name="revisit-after" content="periode">',
    "Set-Cookie": '<meta http-equiv="Set-Cookie" content="name, date">',
    "web_author": '<meta name="web_author" content="text">'
};

function metaTagsButtons(textareaId) {
    var textarea=$(document.getElementById(textareaId));
    var toolbar = $("<div/>");
    toolbar.insertBefore(textarea);
    $('<input type="button" value="description">').click(function(){metaTagsInsert(textareaId, metaTagsButtonList['description']+"\n");}).appendTo(toolbar);
    $('<input type="button" value="keywords">').click(function(){metaTagsInsert(textareaId, metaTagsButtonList['keywords']+"\n");}).appendTo(toolbar);
    //$('<input type="button" value="robots">').click(function(){metaTagsInsert(textareaId, metaTagsButtonList['robots']);}).appendTo(toolbar);
    
    var selector=$("<select style='width:170px;font-size:90%;padding:0px;'><option value=''></option></select>");
    selector.change(function(){metaTagsInsert(textareaId, metaTagsButtonList[selector.val()]+"\n")});
    for(var nm in metaTagsButtonList){
        selector.append($('<option value="'+nm+'">'+nm+'</option>'));
    }
    toolbar.append(selector);
}
// add markup
function metaTagsInsert(id, block) {
    if (document.selection) {
        var newSelection = document.selection.createRange();
        newSelection.text = block;
    } else {
        var textarea = document.getElementById(id);
	// var scrollPosition = textarea.scrollTop;
        if (document.selection) {
                selection = document.selection.createRange().text;
                if ($.browser.msie) { // ie
                        var range = document.selection.createRange(), rangeCopy = range.duplicate();
                        rangeCopy.moveToElementText(textarea);
                        caretPosition = -1;
                        while(rangeCopy.inRange(range)) {
                                rangeCopy.moveStart('character');
                                caretPosition ++;
                        }
                } else { // opera
                        caretPosition = textarea.selectionStart;
                }
        } else { // gecko & webkit
                caretPosition = textarea.selectionStart;
                selection = textarea.value.substring(caretPosition, textarea.selectionEnd);
        } 
        textarea.value = textarea.value.substring(0, caretPosition) + block + textarea.value.substring(caretPosition + selection.length, textarea.value.length);
    }
}
