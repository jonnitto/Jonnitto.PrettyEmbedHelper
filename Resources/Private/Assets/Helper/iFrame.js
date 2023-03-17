import addEvent from './addEvent';
import triggerEvent from './triggerEvent';
import getAriaLabel from './getAriaLabel';
import * as lightboxHelper from '../Helper/Lightbox';

const LOCAL_STORAGE = window.localStorage;
const BASE = 'jonnitto-prettyembed';
const GDPR_CLASS = BASE + '__gdpr';
const GDPR_BUTTON_CLASS = GDPR_CLASS + '-button';
const GDPR_OPEN = {
    youtube: false,
    vimeo: false,
};

const openexternal = (() => {
    const value = document.currentScript.dataset.openexternal;
    return value ? value.split(',') : [];
})();

function markup(node) {
    const DATA = node.dataset;
    const FULLSCREEN = DATA.fs != null;
    if (!DATA.embed) {
        return false;
    }

    return `<iframe src="${DATA.embed}" ${FULLSCREEN ? 'allowfullscreen ' : ''}frameborder="0" allow="${
        FULLSCREEN ? 'fullscreen; ' : ''
    }accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"></iframe>`;
}

function replace(element, tagName) {
    if (typeof element === 'object' && typeof tagName === 'string') {
        const ORIGINAL_ELEMENT = element;
        const ORIGINAL_TAG = ORIGINAL_ELEMENT.tagName;
        const START_RX = new RegExp('^<' + ORIGINAL_TAG, 'i');
        const END_RX = new RegExp(ORIGINAL_TAG + '>$', 'i');
        const START_SUBST = '<' + tagName;
        const END_SUBST = tagName + '>';
        const WRAPPER = document.createElement('div');
        WRAPPER.innerHTML = ORIGINAL_ELEMENT.outerHTML.replace(START_RX, START_SUBST).replace(END_RX, END_SUBST);
        const NEW_ELEMENT = WRAPPER.firstChild;
        element.parentNode.replaceChild(NEW_ELEMENT, element);
        return NEW_ELEMENT;
    }
}

function getImage(node) {
    const IMAGE = node.querySelector('img');
    return {
        node: IMAGE || null,
        src: IMAGE ? IMAGE.getAttribute('src') : null,
    };
}

function getPaddingTop(node, fallback = '56.25%') {
    // 56.25% is a 16:9 in percent

    if (node.dataset.ratio) {
        return node.dataset.ratio;
    }

    const IMAGE = getImage(node);
    if (!IMAGE.node) {
        return fallback;
    }
    const RATIO = (parseInt(IMAGE.node.naturalHeight) / parseInt(IMAGE.node.naturalWidth)) * 100;
    if (typeof RATIO != 'number') {
        return fallback;
    }
    return RATIO + '%';
}

function write(link, playClass, type) {
    checkGdpr(link, type, function () {
        const IFRAME = markup(link);
        const IMAGE = getImage(link);
        if (!IFRAME) {
            return;
        }
        const ELEMENT = replace(link, 'div');
        ELEMENT.classList.add(playClass);
        ELEMENT.style.paddingTop = getPaddingTop(link);
        ELEMENT.innerHTML = IFRAME;

        if (IMAGE.src) {
            ELEMENT.setAttribute('data-img', IMAGE.src);
        }
        triggerEvent({
            type: type,
            style: 'inline',
            title: getAriaLabel(link),
            src: link.dataset.embed,
        });
    });
}

function checkGdpr(element, type, callback) {
    const DATASET = element.dataset;
    const GDPR = DATASET.gdpr;

    if (!GDPR) {
        return callback();
    }

    const STORAGE_KEY = `jonnittoprettyembed_gdpr_${type}`;

    if (LOCAL_STORAGE[STORAGE_KEY] === 'true') {
        element.removeAttribute('data-gdpr');
        return callback();
    }

    if (DATASET.gdprOpen) {
        return;
    }

    GDPR_OPEN[type] = true;

    const WRAPPER = document.createElement('object');
    WRAPPER.classList.add(GDPR_CLASS);
    WRAPPER.classList.add(`${GDPR_CLASS}--${type}`);

    const PANEL = document.createElement('div');
    PANEL.classList.add(`${GDPR_CLASS}-panel`);
    PANEL.innerHTML = `<p>${GDPR}</p>`;

    const BUTTON_CONTAINER = document.createElement('div');
    BUTTON_CONTAINER.innerHTML = `<button data-url="${DATASET.embed}" data-ratio="${
        DATASET.ratio
    }" type="button" class="${GDPR_BUTTON_CLASS} ${GDPR_BUTTON_CLASS}--external">${
        DATASET.gdprNewWindow || 'Open in new window'
    }</button>`;

    const ACCEPT_BUTTON = document.createElement('button');
    ACCEPT_BUTTON.type = 'button';
    ACCEPT_BUTTON.classList.add(GDPR_BUTTON_CLASS);
    ACCEPT_BUTTON.classList.add(`${GDPR_BUTTON_CLASS}--accept`);
    ACCEPT_BUTTON.innerText = DATASET.gdprAccept || 'OK';

    BUTTON_CONTAINER.appendChild(ACCEPT_BUTTON);
    PANEL.appendChild(BUTTON_CONTAINER);
    WRAPPER.appendChild(PANEL);
    element.appendChild(WRAPPER);

    DATASET.gdprOpen = 'true';
    element.setAttribute('data-gdpr-open', true);
    ACCEPT_BUTTON.addEventListener('click', function (event) {
        event.stopPropagation();
        event.preventDefault();
        GDPR_OPEN[type] = false;
        LOCAL_STORAGE[STORAGE_KEY] = 'true';
        [...document.querySelectorAll(`.${GDPR_CLASS}--${type}`)].forEach((el) => {
            el.remove();
        });
        callback();
    });
}

addEvent(`.${GDPR_BUTTON_CLASS}--external`, function (event) {
    event.stopPropagation();
    event.preventDefault();
    const DATASET = event.target.dataset;
    const RATIO = parseFloat(DATASET.ratio || '56.25%');
    const WIDTH = Math.min(window.innerWidth, 1000);
    const HEIGHT = WIDTH * (RATIO / 100);
    const LEFT = (screen.width - WIDTH) / 2;
    const TOP = (screen.height - HEIGHT) / 2;
    window.open(
        DATASET.url,
        '_blank',
        `noopener=yes,directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=${WIDTH},height=${HEIGHT},left=${LEFT},top=${TOP}`
    );
});

function restore(element, playClass) {
    const IMAGE = element.getAttribute('data-img') || false;
    if (!IMAGE) {
        return;
    }
    element.classList.remove(playClass);
    element.removeAttribute('style');
    element.innerHTML = `<img src="${IMAGE}" />`;
    replace(element, 'a');
}

function lightbox(type) {
    if (openexternal.includes(type)) {
        return;
    }

    const SELECTOR = `a.${BASE}--${type}.${BASE}--lightbox`;

    lightboxHelper.init(SELECTOR, function (event) {
        const element = this;
        const HTML = markup(element);
        if (!HTML) {
            return;
        }
        event.preventDefault();
        checkGdpr(element, type, function () {
            const PADDING_TOP = getPaddingTop(element);
            lightboxHelper.get([type, 'iframe'], PADDING_TOP).innerHTML = HTML;
            lightboxHelper.show(() => {
                const dataset = element.dataset;
                if (!dataset.init) {
                    dataset.init = true;
                    triggerEvent({
                        type: type,
                        style: 'lightbox',
                        title: getAriaLabel(element),
                        src: dataset.embed,
                    });
                }
            });
        });
    });
}

function embed(type) {
    if (openexternal.includes(type)) {
        return;
    }
    const SELECTOR = `a.${BASE}--${type}.${BASE}--inline`;
    const PLAY_CLASS = `${BASE}--play`;

    addEvent(SELECTOR, function (event) {
        event.preventDefault();
        write(this, PLAY_CLASS, type);
    });
}

export { markup, replace, write, restore, lightbox, embed };
