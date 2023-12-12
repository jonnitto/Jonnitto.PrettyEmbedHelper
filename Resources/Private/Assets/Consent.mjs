import Consent from './Plugins/Consent';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Consent);
});
