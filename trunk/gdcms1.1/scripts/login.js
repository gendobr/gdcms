/*
 * requires jQuery
 */

// вызов после получения ответа
var showResponse=function (responseText, statusText)  {
    // для обычного html ответа, первый аргумент - свойство responseText
    // объекта XMLHttpRequest

    // если применяется метод ajaxSubmit (или ajaxForm) с использованием опции dataType
    // установленной в 'xml', первый аргумент - свойство responseXML
    // объекта XMLHttpRequest

    // если применяется метод ajaxSubmit (или ajaxForm) с использованием опции dataType
    // установленной в 'json', первый аргумент - объек json, возвращенный сервером.
    // alert('Статус ответа сервера: ' + statusText + '\n\nТекст ответа сервера: \n' + responseText +
    //       '\n\nЦелевой элемент div обновиться этим текстом.');
    eval('var data='+responseText);
    $('#loginoutput').html(data.message);
    //if(responseText=='OK'){
    if(data.status=='OK'){
        if ($.browser.webkit) {
            $('#page_content').html('<div style="font-size:300%;">'+data.message+'...</div>');
        }else{
            window.location.reload();
        }
    }
}

var ajaxloginform=function(){
    // ---- Форма -----
    var options = {
        // элемент, который будет обновлен по ответу сервера
        //target: "#loginoutput",
        success: showResponse, // функция, вызываемая при получении ответа
        timeout: 60000, // тайм-аут
        contentType: "application/x-www-form-urlencoded;charset=UTF-8"
    };

    // привязываем событие submit к форме
    $('#loginform').submit(function() {

        $(this).ajaxSubmit(options);
        // !!! Важно !!!
        // всегда возвращаем false, чтобы предупредить стандартные
        // действия браузера (переход на страницу form.php)
        // alert('Submit was performed.');
        return false;
    });
// ---- Форма -----
    // alert('Load was performed.');
};


$(document).ready(function(){

    $.ajax({
        url: 'index.php?action=login&t='+Math.random(),
        contentType:'text/html; charset=UTF-8',
        dataType:'json',
        success: function(data) {
            console.log(data.message);
            $('#logininput').append(data.message);
            // alert('Load was performed.');
            ajaxloginform();
        }
    });
    //$('#loginoutput').load('index.php?action=login&t='+Math.random());
});
