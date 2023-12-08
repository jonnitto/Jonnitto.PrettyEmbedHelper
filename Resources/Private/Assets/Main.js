import Media from './Plugins/Media';
import Vimeo from './Plugins/Vimeo';
import YouTube from './Plugins/YouTube';
import Gdpr from './Plugins/Gdpr';
import Popup from './Plugins/Popup';
import Magic from './Plugins/Magic';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin([Media, Vimeo, YouTube, Gdpr, Popup, Magic]);
});
