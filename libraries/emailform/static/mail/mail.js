baseurl = "/static/thirdparty/mail/"; //directory location for yes.gif and no.gif
captchaReady = 2; 
checkCaptchaURL = "/contact/checkcaptcha/"; // url for checking captcha. Example: checkcaptcha.php?code= And the captcha code will be inserted after the =
captchaImageURL = "/contact/captcha"; // url for the captcha image. note that ?randomnumber will be inserted at the end.
ajaxSendURL = "/contact/ajaxsend" // URL for ajax sending.

function addLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
        window.onload = func;
    } else {
        window.onload = function() {
            if (oldonload) {
                oldonload();
            }
            func();
        }
    }
}

function mailinit(){
	String.prototype.strip = function(){
		var	str = String(this).replace(/^\s\s*/, ''),
			ws = /\s/,
			i = str.length;
		while (ws.test(str.charAt(--i)));
		return str.slice(0, i + 1);
	}
	String.prototype.capitalize = function(){
		var newstr = "";
		str = String(this).split(" ");
		for (var i=0; i<str.length; i++){
			newstr += str[i].substr(0,1).toUpperCase();
			newstr += str[i].substr(1).toLowerCase();
			if (!(i == str.length-1)) newstr += " ";
		}
		return newstr;
	}
	document.getElementsByClassName = function(cl) {
		var retnode = [];
		var myclass = new RegExp('\\b'+cl+'\\b');
		var elem = this.getElementsByTagName('*');
		for (var i = 0; i < elem.length; i++) {
			var classes = elem[i].className;
			if (myclass.test(classes)) retnode.push(elem[i]);
		}
		return retnode;
	};
	prepareForm();
}

function prepareForm(){
	if (!document.getElementById("mailing")) return;
	if (!document.getElementById("captchaimg")) return;
	var captchaimg = document.getElementById("captchaimg");
	captchaimg.setAttribute("onclick", "reloadCaptcha();");
	captchaimg.style.marginTop = "5px";
	var form = document.getElementById("mailing");
	form.setAttribute("onsubmit", "return ajaxSubmitForm(this);")
	for (var i=0;i<form.elements.length;i++){
		
		if (form.elements[i].getAttribute("type") == "reset"){
			form.elements[i].setAttribute("onclick", "resetImg(this.form);reset()")
		} else if (form.elements[i].getAttribute("type") == "submit"){
			// do nothing
		} else {
			form.elements[i].setAttribute("onchange", "checkValid(this);")
		}
	}
	
}

function resetImg(form){
	reloadCaptcha();
	if (!document.getElementsByClassName("formchkimg")) return;
	var imgs = document.getElementsByClassName("formchkimg");
	for (var i=0;i<imgs.length;i++){
		imgs[i].parentNode.removeChild(imgs[i]);
	}
	if (!document.getElementById("captchacheck")) return;
	var captchachecker = document.getElementById("captchacheck");
	captchachecker.parentNode.removeChild(captchachecker);
	document.getElementById("returnvalue").firstChild.nodeValue = "";
}

function checkValid(field){
	var validFlag = true;
	if (field.parentNode.getElementsByTagName("img")[0]){
		var imgNode = field.parentNode.getElementsByTagName("img")[0]
	} else {
		var imgNode = document.createElement("img");
		imgNode.setAttribute("class", "formchkimg");
		imgNode.width = 15;
		imgNode.height = 15;
		imgNode.style.marginLeft = "4px";
	}

	if (field.name == "email"){
		if (validateEmail(field)){
			imgNode.src = baseurl + "yes.gif";
		} else {
			imgNode.src = baseurl + "no.gif";
			validFlag = false;
		}
		insertAfter(imgNode, field)
	} else if (field.name == "captcha"){
		validateCaptcha(field);
	} else {
		if (validateGeneralField(field)){
			imgNode.src = baseurl + "yes.gif";
		} else {
			imgNode.src = baseurl + "no.gif";
			validFlag = false;
		}
		insertAfter(imgNode, field)
	}
	return validFlag;
}

function validateCaptcha(field){
	if (document.getElementById("captchacheck")){
		var imgNode = document.getElementById("captchacheck");
	} else {
		var imgNode = document.createElement("img");
		imgNode.setAttribute("id", "captchacheck");
		imgNode.width = 15;
		imgNode.height = 15;
		imgNode.style.marginLeft = "4px";
	}
	var xmlhttp = getAjaxObj();
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState == 4){
			if (xmlhttp.responseText == "1"){
				imgNode.src = baseurl + "yes.gif";
				captchaReady = true;
			} else {
				imgNode.src = baseurl + "no.gif";
				captchaReady = false;
			}
			insertAfter(imgNode, field);
		}
	}
	xmlhttp.open("GET", checkCaptchaURL + field.value.strip(), true);
	xmlhttp.send();
}

function validateGeneralField(field){
	var value = field.value;
	if (value.length <= 0){
		return false;
	} else {
		return true;
	}
}

function validateMailing(form){
	var validFlag = true;
	for (var i=0;i<form.elements.length;i++){
		var type = form.elements[i].getAttribute("type");
		if (type == "text" || form.elements[i].name == "msg"){
			if (checkValid(form.elements[i])){
				continue;
			} else {
				validFlag = false;
				continue;
			}
		}
	}
	if (!captchaReady) validFlag = false;
	return validFlag;
}

function validateEmail(field){
	field.value = field.value.strip();
	var emailpattern = new RegExp("^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-z]{2,4}$");
	return emailpattern.test(field.value);
}

function reloadCaptcha(){
	var captcha = document.getElementById("captchaimg");
	captcha.src = captchaImageURL + "?" +Math.round(Math.random()*1000);	
}

function ajaxSubmitForm(form){
	if (!validateMailing(form)){
		displayMsg("Invalid data");
		return false;
	}
	var data = "";
	for (var i=0; i<form.elements.length; i++){
		data += form.elements[i].name;
		data += "=";
		data += escape(form.elements[i].value);
		data += "&";
	}
	xmlhttp = getAjaxObj();
	if (xmlhttp){
		xmlhttp.onreadystatechange = function(){
			if (xmlhttp.readyState == 4){
				if (xmlhttp.status == 200 || xmlhttp.status == 304){
					displayMsg(xmlhttp.responseText.split("^")[0]);
				} else {
					displayMsg("Failed " + xmlhttp.status);
				}
			}
		};
		xmlhttp.open("POST", ajaxSendURL, true);
		xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xmlhttp.send(data);
		return false;
	} else {
		return true;
	}
}

function displayMsg(msg){
	var location = document.getElementById("returnvalue");
	if (!location){
		location = document.createElement("span");
		location.id = "returnvalue";
		location.style.marginLeft = "5px";
		location.style.color = "red";
		location.style.fontWeight = "bold";
		location.appendChild(document.createTextNode(msg));
		insertAfter(location, document.getElementById("button2"));
	}
	location.firstChild.nodeValue = msg;
}

function getAjaxObj(){
	var xmlhttp = false;
		if (window.XMLHttpRequest){
		xmlhttp = new XMLHttpRequest();
	} else if (window.ActiveXObject){
		try{
			xmlhttp = new ActivXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {
				xmlhttp = false;
			}
		}
	}
	return xmlhttp;
}


function insertAfter(node, baseNode){
	baseNode.parentNode.insertBefore(node, baseNode.nextSibling);
}

addLoadEvent(mailinit);