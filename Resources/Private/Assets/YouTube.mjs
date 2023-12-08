import YouTube from './Plugins/YouTube';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(YouTube);
});
