import Gator from 'gator';

const BASE_CLASS = 'jonnitto-prettyembed';
const INIT_CLASS = `${BASE_CLASS}--init`;
const PLAY_CLASS = `${BASE_CLASS}--play`;
const SLIM_CLASS = `${BASE_CLASS}--slim`;
const VIDEOS = document.querySelectorAll(
    `.${BASE_CLASS}--video video:not([autoplay])`
);

function init(video) {
    let classList = video.parentNode.classList;
    if (classList.contains(INIT_CLASS)) {
        return;
    }

    if (video.hasAttribute('data-controls')) {
        video.setAttribute('controls', true);
    }

    if (!video.hasAttribute('controls')) {
        classList.add(SLIM_CLASS);
        Gator(video).on('click', () => {
            let play = !classList.contains(PLAY_CLASS);
            classList[play ? 'add' : 'remove'](PLAY_CLASS);
            if (play) {
                video.play();
            } else {
                video.pause();
            }
        });
    }

    classList.add(INIT_CLASS);
    classList.add(PLAY_CLASS);
    setTimeout(() => {
        video.play();
    }, 0);
}

function pause(videos = VIDEOS, current = null) {
    for (let index = 0; index < videos.length; index++) {
        const video = VIDEOS[index];
        if (video != current) {
            if (!video.hasAttribute('controls')) {
                video.parentNode.classList.remove(PLAY_CLASS);
            }
            video.pause();
        }
    }
}

for (let index = 0; index < VIDEOS.length; index++) {
    Gator(VIDEOS[index]).on('play', function() {
        pause(VIDEOS, this);
    });
}

export { init, pause };
