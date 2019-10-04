export default function(selector, callback) {
    document.documentElement.addEventListener('click', event => {
        let element = event.target.closest(selector);
        if (!element || typeof callback != 'function') {
            return;
        }
        callback.call(element, event);
    });
}
