import { init } from '../Helper/MediaInit';
import addEvent from '../Helper/addEvent';

const elements = document.querySelectorAll(
    '.jonnitto-prettyembed--audio audio'
);
Array.from(elements).forEach(element => {
    init(element, false);
});
