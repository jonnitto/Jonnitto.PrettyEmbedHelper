import * as video from '../Helper/MediaInit';
import * as lightbox from '../Helper/Lightbox';

const BASE_CLASS = 'jonnitto-prettyembed';
const SELECTOR_CLASS = `.${BASE_CLASS}--video.${BASE_CLASS}--lightbox video`;
let timeout;

lightbox.init(SELECTOR_CLASS, function (event) {
    event.preventDefault();
    clearTimeout(timeout);
    video.pause();
    const VIDEO_NODE = lightbox.get('video', false).appendChild(this.cloneNode(true));
    if (this.dataset.controls == undefined) {
        // As we have no controls, we need to add also the play and pause button
        Array.from(this.parentNode.children).forEach((element) => {
            if (element != this && !element.classList.contains(`${BASE_CLASS}__preview`)) {
                VIDEO_NODE.parentNode.appendChild(element.cloneNode(true));
            }
        });
    }
    lightbox.show(() => {
        video.init(VIDEO_NODE);
    });
    timeout = setTimeout(function () {
        // Make sure the VIDEO_NODE start playing
        VIDEO_NODE.play();
    }, 500);
});
