import Vimeo from './Plugins/Vimeo';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Vimeo);
});
