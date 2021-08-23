import { init } from '../Helper/MediaInit';
import addEvent from '../Helper/addEvent';
import triggerEvent from '../Helper/triggerEvent';
import getAriaLabel from '../Helper/getAriaLabel';

const SELECTOR = '.jonnitto-prettyembed--video.jonnitto-prettyembed--inline video';

addEvent(SELECTOR, function (event) {
    event.preventDefault();
    init(this, true, () => {
        triggerEvent({
            type: 'video',
            style: 'inline',
            title: getAriaLabel(this),
            src: (() => {
                const SOURCE = this.querySelector('source');
                return SOURCE ? SOURCE.src : null;
            })(),
        });
    });
});
