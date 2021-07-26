document.body.className = document.body.className.replace('no-js','js');;
try{document.getElementById('user_login').focus();}catch(e){}
		if(typeof wpOnload=='function')wpOnload();;
/(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);