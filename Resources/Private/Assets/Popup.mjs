import Popup from './Plugins/Popup';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Popup);
});
