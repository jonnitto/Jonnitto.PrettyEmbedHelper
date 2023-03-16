!function(){"use strict";var t="jonnitto-prettyembed",e="".concat(t,"--init"),n="".concat(t,"--slim"),o="".concat(t,"--play"),r=Array.from(document.querySelectorAll(".".concat(t,"--video video:not([autoplay])"))),a=Array.from(document.querySelectorAll(".".concat(t,"--audio audio:not([autoplay])"))),c=[].concat(r,a);function i(t){var r=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],a=arguments.length>2?arguments[2]:void 0,c=t.parentNode.classList;if(!c.contains(e))if("function"==typeof a&&a(),t.hasAttribute("data-controls")&&t.setAttribute("controls",!0),t.hasAttribute("controls")||(c.add(n),t.addEventListener("click",(function(){var e=!c.contains(o);c[e?"add":"remove"](o),e?t.play():t.pause()}))),t.hasAttribute("data-streaming")){var i=t.getAttribute("data-streaming");if(t.canPlayType("application/vnd.apple.mpegurl"))t.src=i,u(t,r,c);else if("undefined"==typeof Hls){var l=document.createElement("script");l.src="/_Resources/Static/Packages/Jonnitto.PrettyEmbedHelper/Scripts/Hls.js?v=1",document.head.appendChild(l),l.addEventListener("load",(function(){setTimeout((function(){d(t,i),u(t,r,c)}),100)}))}else d(t,i),u(t,r,c)}else u(t,r,c)}function u(t,n,r){r.add(e),n&&(r.add(o),setTimeout((function(){t.play()}),0))}function l(){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;(arguments.length>0&&void 0!==arguments[0]?arguments[0]:c).forEach((function(e){e!=t&&(e.hasAttribute("controls")||e.parentNode.classList.remove(o),e.pause())}))}function d(t,e){if(Hls.isSupported()){var n=new Hls;n.loadSource(e),n.attachMedia(t)}}function s(t){var e;t||(t={}),window.CustomEvent?e=new CustomEvent("prettyembed",{detail:t}):(e=document.createEvent("CustomEvent")).initCustomEvent("prettyembed",!0,!0,t),document.dispatchEvent(e)}c.forEach((function(t){t.addEventListener("play",(function(t){l(c,t.target)}))}));var p=document.querySelectorAll(".jonnitto-prettyembed--audio audio");function f(t,e){return(t.matches||t.matchesSelector||t.msMatchesSelector||t.mozMatchesSelector||t.webkitMatchesSelector||t.oMatchesSelector).call(t,e)}function m(t,e){document.documentElement.addEventListener("click",(function(n){var o=function(t,e){var n=t;do{if(f(n,e))return n;n=n.parentElement||n.parentNode}while(null!==n&&1===n.nodeType);return null}(n.target,t);o&&"function"==typeof e&&e.call(o,n)}))}function v(t){return t.getAttribute("aria-label")}Array.from(p).forEach((function(t){var e;i(t,!1),s({type:"audio",style:"inline",src:(e=t.querySelector("source"),e?e.src:null)})}));function y(t){return y="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},y(t)}function b(t){return function(t){if(Array.isArray(t))return h(t)}(t)||function(t){if("undefined"!=typeof Symbol&&null!=t[Symbol.iterator]||null!=t["@@iterator"])return Array.from(t)}(t)||function(t,e){if(!t)return;if("string"==typeof t)return h(t,e);var n=Object.prototype.toString.call(t).slice(8,-1);"Object"===n&&t.constructor&&(n=t.constructor.name);if("Map"===n||"Set"===n)return Array.from(t);if("Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n))return h(t,e)}(t)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function h(t,e){(null==e||e>t.length)&&(e=t.length);for(var n=0,o=new Array(e);n<e;n++)o[n]=t[n];return o}m(".jonnitto-prettyembed--video.jonnitto-prettyembed--inline video",(function(t){var e=this;t.preventDefault(),i(this,!0,(function(){var t;s({type:"video",style:"inline",title:v(e),src:(t=e.querySelector("source"),t?t.src:null)})}))}));var g=document.documentElement,E=document.body,A="jonnitto-prettyembed",w="".concat(A,"__lightbox"),S=w,L="-".concat(w),T=g.classList,j="".concat(A,"__inner"),_="".concat(A,"__close"),C="".concat(A,"__content"),H=document.createElement("div");H.className=w,H.innerHTML='\n<div class="'.concat(j,'">\n    <button type="button" class="').concat(_,'">&times;</button>\n    <div id="').concat(S,'" class="').concat(A," ").concat(C,'"></div>\n</div>');var M=!1,q=null;function x(){M.className="".concat(A," ").concat(C),M.removeAttribute("style"),M.innerHTML=""}function N(t,e){return M||(E.appendChild(H),M=document.getElementById(S)),x(),"object"!=y(t)&&(t=t?[t]:[]),t.forEach((function(t){M.classList.add("".concat(A,"--").concat(t))})),e&&(M.style.paddingTop=e),M}function k(t){clearTimeout(q),q=setTimeout((function(){"function"==typeof t&&t(),T.add(L)}),100)}function I(){T.remove(L),M&&(clearTimeout(q),q=setTimeout(x,300))}function O(t,e){"function"==typeof e&&"string"==typeof t&&m(t,e)}m(".".concat(C),(function(t){t.stopImmediatePropagation()})),m(".".concat(w),I),m(".".concat(_),I),g.addEventListener("keyup",(function(t){27==t.keyCode&&T.contains(L)&&I()}));var D,P="jonnitto-prettyembed";O(".".concat(P,"--video.").concat(P,"--lightbox video"),(function(t){var e=this;t.preventDefault(),clearTimeout(D),l();var n=N("video",!1).appendChild(this.cloneNode(!0));null==this.dataset.controls&&Array.from(this.parentNode.children).forEach((function(t){t==e||t.classList.contains("".concat(P,"__preview"))||n.parentNode.appendChild(t.cloneNode(!0))})),k((function(){var t;i(n),e.dataset.init||(e.dataset.init=!0,s({type:"video",style:"lightbox",title:v(e),src:(t=e.querySelector("source"),t?t.src:null)}))})),D=setTimeout((function(){n.play()}),500)}));var W,R=window.localStorage,z="jonnitto-prettyembed__gdpr",$=z+"-button",B=(W=document.currentScript.dataset.openexternal)?W.split(","):[],F="jonnitto-prettyembed";function J(t){var e=t.dataset,n=null!=e.fs;return!!e.embed&&'<iframe src="'.concat(e.embed,'" ').concat(n?"allowfullscreen ":"",'frameborder="0" allow="').concat(n?"fullscreen; ":"",'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"></iframe>')}function K(t){var e=t.querySelector("img");return{node:e||null,src:e?e.getAttribute("src"):null}}function U(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"56.25%";if(t.dataset.ratio)return t.dataset.ratio;var n=K(t);if(!n.node)return e;var o=parseInt(n.node.naturalHeight)/parseInt(n.node.naturalWidth)*100;return"number"!=typeof o?e:o+"%"}function G(t,e,n){Q(t,n,(function(){var o=J(t),r=K(t);if(o){var a=function(t,e){if("object"===y(t)&&"string"==typeof e){var n=t,o=n.tagName,r=new RegExp("^<"+o,"i"),a=new RegExp(o+">$","i"),c="<"+e,i=e+">",u=document.createElement("div");u.innerHTML=n.outerHTML.replace(r,c).replace(a,i);var l=u.firstChild;return t.parentNode.replaceChild(l,t),l}}(t,"div");a.classList.add(e),a.style.paddingTop=U(t),a.innerHTML=o,r.src&&a.setAttribute("data-img",r.src),s({type:n,style:"inline",title:v(t),src:t.dataset.embed})}}))}function Q(t,e,n){var o=t.dataset,r=o.gdpr;if(!r)return n();var a="jonnittoprettyembed_gdpr_".concat(e);if("true"===R[a])return t.removeAttribute("data-gdpr"),n();if(!o.gdprOpen){var c=document.createElement("object");c.classList.add(z),c.classList.add("".concat(z,"--").concat(e)),c.innerHTML="<p>".concat(r,"</p>");var i=document.createElement("div");i.innerHTML='<button data-url="'.concat(o.embed,'" data-ratio="').concat(o.ratio,'" type="button" class="').concat($," ").concat($,'--external">').concat(o.gdprNewWindow||"Open in new window","</button>");var u=document.createElement("button");u.type="button",u.classList.add($),u.classList.add("".concat($,"--accept")),u.innerText=o.gdprAccept||"OK",i.appendChild(u),c.appendChild(i),t.appendChild(c),o.gdprOpen="true",t.setAttribute("data-gdpr-open",!0),u.addEventListener("click",(function(t){t.stopPropagation(),t.preventDefault(),R[a]="true",b(document.querySelectorAll(".".concat(z,"--").concat(e))).forEach((function(t){t.remove()})),n()}))}}function V(t){B.includes(t)||O("a.".concat(F,"--").concat(t,".").concat(F,"--lightbox"),(function(e){var n=this,o=J(n);o&&(e.preventDefault(),Q(n,t,(function(){var e=U(n);N([t,"iframe"],e).innerHTML=o,k((function(){var e=n.dataset;e.init||(e.init=!0,s({type:t,style:"lightbox",title:v(n),src:e.embed}))}))})))}))}function X(t){if(!B.includes(t)){var e="a.".concat(F,"--").concat(t,".").concat(F,"--inline"),n="".concat(F,"--play");m(e,(function(e){e.preventDefault(),G(this,n,t)}))}}function Y(t){var e=t.getAttribute("src");t.naturalHeight<=90&&t.naturalWidth<=120&&-1==e.indexOf("/default.jpg")&&(e=e.replace("mqdefault","default").replace("hqdefault","mqdefault").replace("sddefault","hqdefault").replace("maxresdefault","sddefault"),t.setAttribute("src",e),setTimeout((function(){t.onload=function(){Y(t)}}),10),setTimeout((function(){Y(t)}),5e3))}m(".".concat($,"--external"),(function(t){t.stopPropagation(),t.preventDefault();var e=t.target.dataset,n=parseFloat(e.ratio||"56.25%"),o=Math.min(window.innerWidth,1e3),r=o*(n/100),a=(screen.width-o)/2,c=(screen.height-r)/2;window.open(e.url,"_blank","noopener=yes,directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=".concat(o,",height=").concat(r,",left=").concat(a,",top=").concat(c))})),X("vimeo"),V("vimeo"),X("youtube"),V("youtube"),window.addEventListener("load",(function(){!function(t){void 0===t&&(t=document.querySelectorAll("img.jonnitto-prettyembed__youtube-preview"));for(var e=t.length-1;e>=0;e--)Y(t[e])}()}))}();
//# sourceMappingURL=Main.js.map
