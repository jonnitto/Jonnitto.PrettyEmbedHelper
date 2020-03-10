/*!
 * Jonnitto.PrettyEmbedHelper - created by Jon Uhlmann
 * @link https://github.com/jonnitto/Jonnitto.PrettyEmbedHelper
 * Copyright 2019-2020 Jon Uhlmann
 * Licensed under GPL-3.0-or-later
 */

!function(){"use strict";function t(t){!function(){var t=!(arguments.length>0&&void 0!==arguments[0])||arguments[0],e=arguments.length>1?arguments[1]:void 0,n=!/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(window.navigator.userAgent);!!(!n&&!t||n&&t)&&("function"==typeof e&&e())}(!0,t)}function e(t){var n=t.getAttribute("src");t.naturalHeight<=90&&t.naturalWidth<=120&&-1==n.indexOf("/default.jpg")&&(n=n.replace("mqdefault","default").replace("hqdefault","mqdefault").replace("sddefault","hqdefault").replace("maxresdefault","sddefault"),t.setAttribute("src",n),setTimeout((function(){t.onload=function(){e(t)}}),10),setTimeout((function(){e(t)}),5e3))}window.addEventListener("load",(function(){var n;t((function(){void 0===n&&(n=document.querySelectorAll("img.jonnitto-prettyembed__youtube-preview"));for(var t=n.length-1;t>=0;t--)e(n[t])}))}))}();
//# sourceMappingURL=Backend.js.map
