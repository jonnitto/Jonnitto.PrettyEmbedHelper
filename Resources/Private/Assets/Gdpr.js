import Gdpr from './Plugins/Gdpr';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Gdpr);
});
