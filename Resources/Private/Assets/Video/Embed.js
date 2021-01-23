import { init } from '../Helper/MediaInit';
import addEvent from '../Helper/addEvent';
import triggerEvent from '../Helper/triggerEvent';

const SELECTOR = '.jonnitto-prettyembed--video.jonnitto-prettyembed--inline video';

addEvent(SELECTOR, function (event) {
    event.preventDefault();
    init(this);
    triggerEvent({
        type: 'video',
        style: 'inline',
        src: (() => {
            const SOURCE = this.querySelector('source');
            return SOURCE ? SOURCE.src : null;
        })(),
    });
});
