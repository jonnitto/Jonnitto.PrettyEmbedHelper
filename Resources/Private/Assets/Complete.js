import Consent from './Plugins/Consent';
import Media from './Plugins/Media';
import Methods from './Plugins/Methods';
import Popup from './Plugins/Popup';
import Vimeo from './Plugins/Vimeo';
import YouTube from './Plugins/YouTube';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin([Consent, Media, Methods, Popup, Vimeo, YouTube]);
});
