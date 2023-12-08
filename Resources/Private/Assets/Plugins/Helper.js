let youtubeScript = false;
let vimeoScript = false;

function loadScript(src, callback) {
    const element = document.createElement('script');
    element.setAttribute('type', 'text/javascript');
    element.setAttribute('defer', true);
    element.setAttribute('src', src);
    if (typeof callback == 'function') {
        element.addEventListener('load', callback);
    }
    document.head.appendChild(element);
}

function loadVimeoApi(callback) {
    const checkCallback = () => {
        checkIfLoaded(() => window.Vimeo, callback);
    };
    if (vimeoScript) {
        checkCallback();
        return;
    }
    vimeoScript = true;
    loadScript('https://player.vimeo.com/api/player.js', checkCallback);
}

function loadYoutubeApi(callback) {
    const checkCallback = () => {
        checkIfLoaded(() => window.YT?.loaded, callback);
    };
    if (youtubeScript) {
        checkCallback();
        return;
    }
    youtubeScript = true;
    loadScript('https://www.youtube.com/iframe_api', checkCallback);
}

/* Helper functions */
function rafTimeOut(callback, delay) {
    const raf = window.requestAnimationFrame;
    let start = Date.now();
    let stop = false;
    const timeoutFunc = () => {
        Date.now() - start < delay ? stop || raf(timeoutFunc) : callback();
    };
    raf(timeoutFunc);
    return {
        clear: () => (stop = true),
    };
}

function checkIfLoaded(check, callback, maxAttempts = 100) {
    if ((typeof check != 'function' && typeof callback != 'function') || maxAttempts <= 0) {
        return;
    }
    if (check()) {
        callback();
        return;
    }
    maxAttempts--;
    rafTimeOut(() => checkIfLoaded(check, callback, maxAttempts), 100);
}

export { loadYoutubeApi, loadVimeoApi, loadScript };
