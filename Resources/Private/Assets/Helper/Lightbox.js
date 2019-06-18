import Gator from 'gator';

const HTML_ELEMENT = document.documentElement;
const BODY_ELEMENT = document.body;
const BASE_CLASS = 'jonnitto-prettyembed';
const LIGHTBOX_CLASS = `${BASE_CLASS}__lightbox`;
const ID = LIGHTBOX_CLASS;
const VISIBLE_CLASS = `-${LIGHTBOX_CLASS}`;
const VISIBLE_CLASS_LIST = HTML_ELEMENT.classList;
const INNER_CLASS = `${BASE_CLASS}__inner`;
const CLOSE_CLASS = `${BASE_CLASS}__close`;
const CONTENT_CLASS = `${BASE_CLASS}__content`;

let lighboxContent = false;
let lighbox = document.createElement('div');
let timeout = null;

lighbox.className = LIGHTBOX_CLASS;
lighbox.innerHTML = `
<div class="${INNER_CLASS}">
    <button type="button" class="${CLOSE_CLASS}">&times;</button>
    <div id="${ID}" class="${BASE_CLASS} ${CONTENT_CLASS}"></div>
</div>`;

function resetContent() {
    // Reset the content to the default class
    lighboxContent.className = `${BASE_CLASS} ${CONTENT_CLASS}`;
    // Clear all content
    lighboxContent.innerHTML = '';
}

function get(type, paddingTop) {
    if (!lighboxContent) {
        BODY_ELEMENT.appendChild(lighbox);
        lighboxContent = document.getElementById(ID);
    }
    resetContent();
    if (typeof type != 'object') {
        type = type ? [type] : [];
    }
    type.forEach(item => {
        lighboxContent.classList.add(`${BASE_CLASS}--${item}`);
    });

    if (paddingTop) {
        lighboxContent.style.paddingTop = paddingTop;
    }
    return lighboxContent;
}

function show(callback) {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
        if (typeof callback == 'function') {
            callback();
        }
        VISIBLE_CLASS_LIST.add(VISIBLE_CLASS);
    }, 100);
}

function closeLighbox() {
    VISIBLE_CLASS_LIST.remove(VISIBLE_CLASS);
    if (lighboxContent) {
        clearTimeout(timeout);
        timeout = setTimeout(resetContent, 300);
    }
}

function closeLighboxOnESC(event) {
    if (event.keyCode == 27) {
        if (VISIBLE_CLASS_LIST.contains(VISIBLE_CLASS)) {
            closeLighbox();
        }
    }
}

function init(selector, callback) {
    if (typeof callback == 'function' && typeof selector == 'string') {
        Gator(HTML_ELEMENT).on('click', selector, callback);
    }
}

// Catch click event to prevent closing lightbox if some clicks on content
Gator(HTML_ELEMENT).on('click', `.${CONTENT_CLASS}`, event => {
    event.stopPropagation();
});

// Close lightbox on click on lightbox background or on close button
Gator(HTML_ELEMENT).on(
    'click',
    `.${LIGHTBOX_CLASS},.${CLOSE_CLASS}`,
    closeLighbox
);

// Close on ESC
Gator(HTML_ELEMENT).on('keyup', closeLighboxOnESC);

export { get, show, init };
