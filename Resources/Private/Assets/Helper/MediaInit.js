const BASE = 'jonnitto-prettyembed';
const INIT_CLASS = `${BASE}--init`;
const SLIM_CLASS = `${BASE}--slim`;
const PLAY_CLASS = `${BASE}--play`;

const VIDEOS = Array.from(document.querySelectorAll(`.${BASE}--video video:not([autoplay])`));
const AUDIOS = Array.from(document.querySelectorAll(`.${BASE}--audio audio:not([autoplay])`));
const ELEMENTS = [].concat(VIDEOS, AUDIOS);

function init(element, autoplay = true, callback) {
    const CLASS_LIST = element.parentNode.classList;
    if (CLASS_LIST.contains(INIT_CLASS)) {
        return;
    }

    if (typeof callback == 'function') {
        callback();
    }

    if (element.hasAttribute('data-controls')) {
        element.setAttribute('controls', true);
    }

    if (!element.hasAttribute('controls')) {
        CLASS_LIST.add(SLIM_CLASS);
        element.addEventListener('click', () => {
            const play = !CLASS_LIST.contains(PLAY_CLASS);
            CLASS_LIST[play ? 'add' : 'remove'](PLAY_CLASS);
            if (play) {
                element.play();
            } else {
                element.pause();
            }
        });
    }

    if (element.hasAttribute('data-streaming')) {
        const SRC = element.getAttribute('data-streaming');
        if (element.canPlayType('application/vnd.apple.mpegurl')) {
            element.src = SRC;
            addClassAndPlay(element, autoplay, CLASS_LIST);
        } else {
            if (typeof Hls === 'undefined') {
                const HLS_SCRIPT = document.createElement('script');
                HLS_SCRIPT.src = '/_Resources/Static/Packages/Jonnitto.PrettyEmbedHelper/Scripts/Hls.js?v=1';
                document.head.appendChild(HLS_SCRIPT);
                HLS_SCRIPT.addEventListener('load', () => {
                    setTimeout(() => {
                        loadHls(element, SRC);
                        addClassAndPlay(element, autoplay, CLASS_LIST);
                    }, 100);
                });
            } else {
                loadHls(element, SRC);
                addClassAndPlay(element, autoplay, CLASS_LIST);
            }
        }
    } else {
        addClassAndPlay(element, autoplay, CLASS_LIST);
    }
}

function addClassAndPlay(element, autoplay, classList) {
    classList.add(INIT_CLASS);
    if (autoplay) {
        classList.add(PLAY_CLASS);
        setTimeout(() => {
            element.play();
        }, 0);
    }
}

function pause(elements = ELEMENTS, current = null) {
    elements.forEach((element) => {
        if (element != current) {
            if (!element.hasAttribute('controls')) {
                element.parentNode.classList.remove(PLAY_CLASS);
            }
            element.pause();
        }
    });
}

function loadHls(element, src) {
    if (Hls.isSupported()) {
        const HLS = new Hls();
        HLS.loadSource(src);
        HLS.attachMedia(element);
    }
}

ELEMENTS.forEach((element) => {
    element.addEventListener('play', (event) => {
        pause(ELEMENTS, event.target);
    });
});

export { init, pause };
