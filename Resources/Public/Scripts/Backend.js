/*!
 * Jonnitto.PrettyEmbedHelper - created by Jon Uhlmann
 * @link https://github.com/jonnitto/Jonnitto.PrettyEmbedHelper
 * Copyright 2019-2020 Jon Uhlmann
 * Licensed under GPL-3.0-or-later
 */

!function(){"use strict";function e(e){!function(){var e=!(arguments.length>0&&void 0!==arguments[0])||arguments[0],t=arguments.length>1?arguments[1]:void 0,n=!/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(window.navigator.userAgent);!!(!n&&!e||n&&e)&&("function"==typeof t&&t())}(!0,e)}function t(e){var n=e.getAttribute("src");e.naturalHeight<=90&&e.naturalWidth<=120&&-1==n.indexOf("/default.jpg")&&(n=n.replace("mqdefault","default").replace("hqdefault","mqdefault").replace("sddefault","hqdefault").replace("maxresdefault","sddefault"),e.setAttribute("src",n),setTimeout((function(){e.onload=function(){t(e)}}),10),setTimeout((function(){t(e)}),5e3))}function n(n){e((function(){void 0===n&&(n=document.querySelectorAll("img.jonnitto-prettyembed__youtube-preview"));for(var e=n.length-1;e>=0;e--)t(n[e])}))}function o(e){var t="Jonnitto.PrettyEmbedYoutube:YouTube";try{var o=e.detail.node;("function"==typeof o.get&&o.get("nodeType")===t||"string"==typeof o.nodeType&&o.nodeType===t)&&n()}catch(e){}}window.addEventListener("load",(function(){n()})),["NodeCreated","NodeSelected"].forEach((function(e){document.addEventListener("Neos."+e,o,!1)}))}();
//# sourceMappingURL=Backend.js.map
