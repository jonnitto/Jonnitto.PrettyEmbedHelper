import Vimeo from './Plugins/Vimeo';
import YouTube from './Plugins/YouTube';
import Gdpr from './Plugins/Gdpr';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin([Vimeo, YouTube, Gdpr]);
});
