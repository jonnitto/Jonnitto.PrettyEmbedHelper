const BASE = 'jonnitto-prettyembed';
const INIT_CLASS = `${BASE}--init`;
const SLIM_CLASS = `${BASE}--slim`;
const PLAY_CLASS = `${BASE}--play`;

const VIDEOS = Array.from(document.querySelectorAll(`.${BASE}--video video:not([autoplay])`));
const AUDIOS = Array.from(document.querySelectorAll(`.${BASE}--audio audio:not([autoplay])`));
const ELEMENTS = [].concat(VIDEOS, AUDIOS);

function init(element, autoplay = true) {
    const CLASS_LIST = element.parentNode.classList;
    if (CLASS_LIST.contains(INIT_CLASS)) {
        return;
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

    CLASS_LIST.add(INIT_CLASS);
    if (autoplay) {
        CLASS_LIST.add(PLAY_CLASS);
        setTimeout(() => {
            element.play();
        }, 0);
    }
}

function pause(elements = ELEMENTS, current = null) {
    elements.forEach(element => {
        if (element != current) {
            if (!element.hasAttribute('controls')) {
                element.parentNode.classList.remove(PLAY_CLASS);
            }
            element.pause();
        }
    });
}

ELEMENTS.forEach(element => {
    element.addEventListener('play', event => {
        pause(ELEMENTS, event.target);
    });
});

export { init, pause };
