import Methods from './Plugins/Methods';

window.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Methods);
});
