import { init } from '../Helper/MediaInit';
import triggerEvent from '../Helper/triggerEvent';

console.log(triggerEvent);

const elements = document.querySelectorAll('.jonnitto-prettyembed--audio audio');
Array.from(elements).forEach((element) => {
    init(element, false);
    triggerEvent({
        type: 'audio',
        style: 'inline',
        src: (() => {
            const SOURCE = element.querySelector('source');
            return SOURCE ? SOURCE.src : null;
        })(),
    });
});
