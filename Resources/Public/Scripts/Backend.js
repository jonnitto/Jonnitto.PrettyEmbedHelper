/*!
 * Jonnitto.PrettyEmbedHelper - created by Jon Uhlmann
 * @link https://github.com/jonnitto/Jonnitto.PrettyEmbedHelper
 * Copyright 2019-2020 Jon Uhlmann
 * Licensed under GPL-3.0-or-later
 */
!function(){"use strict";function e(t){var u=t.getAttribute("src");t.naturalHeight<=90&&t.naturalWidth<=120&&-1==u.indexOf("/default.jpg")&&(u=u.replace("mqdefault","default").replace("hqdefault","mqdefault").replace("sddefault","hqdefault").replace("maxresdefault","sddefault"),t.setAttribute("src",u),setTimeout((function(){t.onload=function(){e(t)}}),10),setTimeout((function(){e(t)}),5e3))}window.addEventListener("load",(function(){!function(t){void 0===t&&(t=document.querySelectorAll("img.jonnitto-prettyembed__youtube-preview"));for(var u=t.length-1;u>=0;u--)e(t[u])}()}))}();
//# sourceMappingURL=Backend.js.map
