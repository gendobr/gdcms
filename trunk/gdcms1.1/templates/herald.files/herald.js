function open(url) {
var 	toDate = new Date();
	toDate.setDate(toDate.getDate() - 200);
	document.cookie='lang=0;expires='+toDate.toGMTString()+';';
location.href=url;
}