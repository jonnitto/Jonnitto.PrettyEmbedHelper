export default function (options) {
    let event;
    if (!options) {
        options = {};
    }
    if (window.CustomEvent) {
        event = new CustomEvent('prettyembed', { detail: options });
    } else {
        event = document.createEvent('CustomEvent');
        event.initCustomEvent('prettyembed', true, true, options);
    }
    document.dispatchEvent(event);
}
