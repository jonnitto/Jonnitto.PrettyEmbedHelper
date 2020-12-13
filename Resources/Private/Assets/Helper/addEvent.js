function matches(element, selector) {
    return (
        element.matches ||
        element.matchesSelector ||
        element.msMatchesSelector ||
        element.mozMatchesSelector ||
        element.webkitMatchesSelector ||
        element.oMatchesSelector
    ).call(element, selector);
}

function closest(element, selector) {
    let ancestor = element;

    do {
        if (matches(ancestor, selector)) {
            return ancestor;
        }
        ancestor = ancestor.parentElement || ancestor.parentNode;
    } while (ancestor !== null && ancestor.nodeType === 1);
    return null;
}

export default function (selector, callback) {
    document.documentElement.addEventListener('click', (event) => {
        const ELEMENT = closest(event.target, selector);
        if (!ELEMENT || typeof callback != 'function') {
            return;
        }
        callback.call(ELEMENT, event);
    });
}
