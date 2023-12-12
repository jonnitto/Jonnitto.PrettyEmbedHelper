import Media from './Plugins/Media';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Media);
});
