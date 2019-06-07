import * as video from './Init';
import * as lightbox from '../Helper/Lightbox';

const BASE_CLASS = 'jonnitto-prettyembed';
const SELECTOR_CLASS = `.${BASE_CLASS}--video.${BASE_CLASS}--lightbox video`;
let timeout;

lightbox.init(SELECTOR_CLASS, function(event) {
    event.preventDefault();
    clearTimeout(timeout);
    video.pause();
    let videoNode = lightbox.get('video').appendChild(this.cloneNode(true));
    lightbox.show(() => {
        video.init(videoNode);
    });
    timeout = setTimeout(function() {
        // Make sure the videoNode start playing
        videoNode.play();
    }, 500);
});
