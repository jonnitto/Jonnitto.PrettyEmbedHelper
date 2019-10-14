const BASE_CLASS = 'jonnitto-prettyembed';
const INIT_CLASS = `${BASE_CLASS}--init`;
const PLAY_CLASS = `${BASE_CLASS}--play`;
const SLIM_CLASS = `${BASE_CLASS}--slim`;
const VIDEOS = document.querySelectorAll(
    `.${BASE_CLASS}--video video:not([autoplay])`
);

function init(video) {
    const CLASS_LIST = video.parentNode.classList;
    if (CLASS_LIST.contains(INIT_CLASS)) {
        return;
    }

    if (video.hasAttribute('data-controls')) {
        video.setAttribute('controls', true);
    }

    if (!video.hasAttribute('controls')) {
        CLASS_LIST.add(SLIM_CLASS);
        video.addEventListener('click', () => {
            const play = !CLASS_LIST.contains(PLAY_CLASS);
            CLASS_LIST[play ? 'add' : 'remove'](PLAY_CLASS);
            if (play) {
                video.play();
            } else {
                video.pause();
            }
        });
    }

    CLASS_LIST.add(INIT_CLASS);
    CLASS_LIST.add(PLAY_CLASS);
    setTimeout(() => {
        video.play();
    }, 0);
}

function pause(videos = VIDEOS, current = null) {
    for (let index = 0; index < videos.length; index++) {
        const VIDEO = VIDEOS[index];
        if (VIDEO != current) {
            if (!VIDEO.hasAttribute('controls')) {
                VIDEO.parentNode.classList.remove(PLAY_CLASS);
            }
            VIDEO.pause();
        }
    }
}

for (let index = 0; index < VIDEOS.length; index++) {
    VIDEOS[index].addEventListener('play', event => {
        pause(VIDEOS, event.target);
    });
}

export { init, pause };
