import Gator from 'gator';
import { init } from './Init';

const SELECTOR =
    '.jonnitto-prettyembed--video.jonnitto-prettyembed--inline video';

// Attach the events to the html tag (because of the Google Tag Manager)
Gator(document.documentElement).on('click', SELECTOR, function(event) {
    event.preventDefault();
    init(this);
});
