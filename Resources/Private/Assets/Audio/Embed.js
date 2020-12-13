import { init } from '../Helper/MediaInit';

const elements = document.querySelectorAll('.jonnitto-prettyembed--audio audio');
Array.from(elements).forEach((element) => {
    init(element, false);
});
