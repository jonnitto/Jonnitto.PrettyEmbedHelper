import Magic from './Plugins/Magic';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Magic);
});
