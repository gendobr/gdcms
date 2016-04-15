

var langs;
var default_langs={
    'ukr':'ukr',
    'rus':'rus',
    'eng':'eng'
};

function draw_langstring(finp)
{
    //document.writeln('<div id="'+finp+'_edits" align="right"> </div>');
    finput=finp;
    var finalInput = document.getElementById(finp);
    if(!finalInput) return false;


    var parentNode=finalInput.parentNode;
    p = document.createElement("div");
    p.setAttribute("id", finp+'_edits');
    p.setAttribute("align", 'left');
    parentNode.appendChild(p);

    var old_str=finalInput.value;
    var regexp;
    var matches;
    var default_lang;
    var langstrings=[];

    //var recivedLangs=langsHTTP.responseText;
    var finalStr="";
    //eval("langs="+recivedLangs);

    // get langstrings
    if(!langs)  langs=default_langs;
    for(i in langs)
    {
        if(!default_lang) default_lang=i;
        matches=old_str.match(new RegExp("<"+i+">([^[<]*)</"+i+">"));
        //alert(matches);
        if(matches)
        {
            matches=matches[1];
            old_str=old_str.replace(new RegExp("<"+i+">([^[<]*)</"+i+">"), '<<'+i+'>>');
            old_str=old_str.replace(new RegExp("<"+i+">([^[<]*)</"+i+">","g"), '');
        }else matches='';
        langstrings[i]=matches;
    }

    for(i in langs)
    {
        matches=old_str.replace(new RegExp("<<"+i+">>"), langstrings[i]);
        matches=matches.replace(/<<[^>]+>>/g,'');
        finalStr+= ("<small>"+langs[i] +"</small><br/><input type='text' id="+finp+"_input_"+i+" onchange='refillText(\""+finp+"\");' value='"+matches+"'><br/>");
    }
    if(finalStr!="")
    {
        finalInput.style.height="1px";
        finalInput.style.color="white";
        finalInput.style.backgroundColor="white";
        finalInput.style.border="none";
    }
    var edits = document.getElementById(finp+"_edits");
    edits.innerHTML = finalStr;
}




function draw_langarea(finp)
{
    //document.writeln('<div id="'+finp+'_edits" align="right"> </div>');
    finput=finp;
    var finalInput = document.getElementById(finp);
    if(!finalInput) return false;


    var parentNode=finalInput.parentNode;
    p = document.createElement("div");
    p.setAttribute("id", finp+'_edits');
    p.setAttribute("align", 'left');
    parentNode.appendChild(p);

    var old_str=finalInput.value;
    var regexp;
    var matches;
    var default_lang;
    var langstrings=[];

    //var recivedLangs=langsHTTP.responseText;
    var finalStr="";
    //eval("langs="+recivedLangs);

    // get langstrings
    if(!langs)  langs=default_langs;
    for(i in langs)
    {
        if(!default_lang) default_lang=i;
        matches=old_str.match(new RegExp("<"+i+">([^[<]*)</"+i+">"));
        //alert(matches);
        if(matches)
        {
            matches=matches[1];
            old_str=old_str.replace(new RegExp("<"+i+">([^[<]*)</"+i+">"), '<<'+i+'>>');
            old_str=old_str.replace(new RegExp("<"+i+">([^[<]*)</"+i+">","g"), '');
        }else matches='';
        langstrings[i]=matches;
    }

    for(i in langs)
    {
        matches=old_str.replace(new RegExp("<<"+i+">>"), langstrings[i]);
        matches=matches.replace(/<<[^>]+>>/g,'');
        finalStr+= ("<small>"+langs[i] +"</small><br/><textarea id="+finp+"_input_"+i+" onchange='refillText(\""+finp+"\");'>"+matches+"</textarea><br/>");
    }
    if(finalStr!="")
    {
        finalInput.style.height="1px";
        finalInput.style.color="white";
        finalInput.style.backgroundColor="white";
        finalInput.style.border="none";
    }
    var edits = document.getElementById(finp+"_edits");
    edits.innerHTML = finalStr;
}



function refillText(finp)
{
    var finalText="";
    var str='';
    var tempInp;
    if(!langs)  langs=default_langs;
    for(i in langs)
    {
        //alert(finp+"_input_"+i);
        tempInp = document.getElementById(finp+"_input_"+i);
        str=tempInp.value;
        if(tempInp.value!="") finalText+=("<"+i+">" + str.replace(/>|</g,'?') + "</"+i+">");
    }
    var finalInput = document.getElementById(finp);
    finalInput.value=finalText;
}