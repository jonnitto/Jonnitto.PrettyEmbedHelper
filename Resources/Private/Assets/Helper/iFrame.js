import addEvent from './addEvent';
import { noAutoplay } from './checkAgent';
import * as lightboxHelper from '../Helper/Lightbox';

const BASE = 'jonnitto-prettyembed';

function markup(node) {
    let data = node.dataset;
    let fullscreen = data.fs != null;
    if (!data.embed) {
        return false;
    }

    return `<iframe src="${data.embed}" ${
        fullscreen ? 'allowfullscreen ' : ''
    }frameborder="0" allow="${
        fullscreen ? 'fullscreen; ' : ''
    }accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"></iframe>`;
}

function replace(element, tagName) {
    if (typeof element === 'object' && typeof tagName === 'string') {
        let originalElement = element;
        let originalTag = originalElement.tagName;
        let startRX = new RegExp('^<' + originalTag, 'i');
        let endRX = new RegExp(originalTag + '>$', 'i');
        let startSubst = '<' + tagName;
        let endSubst = tagName + '>';
        let wrapper = document.createElement('div');
        wrapper.innerHTML = originalElement.outerHTML
            .replace(startRX, startSubst)
            .replace(endRX, endSubst);
        let newElement = wrapper.firstChild;
        element.parentNode.replaceChild(newElement, element);
        return newElement;
    }
}

function getImage(node) {
    let image = node.querySelector('img');
    return {
        node: image || null,
        src: image ? image.getAttribute('src') : null
    };
}

function getPaddingTop(node, fallback = '56.25%') {
    // 56.25% is a 16:9 fallback
    let image = getImage(node);
    if (!image.node) {
        return fallback;
    }
    let ratio =
        (parseInt(image.node.naturalHeight) /
            parseInt(image.node.naturalWidth)) *
        100;
    if (typeof ratio != 'number') {
        return fallback;
    }
    return ratio + '%';
}

function write(link, playClass) {
    let iframe = markup(link);
    let image = getImage(link);
    if (iframe && image.src) {
        let element = replace(link, 'div');
        element.setAttribute('data-img', image.src);
        element.classList.add(playClass);
        element.style.paddingTop = getPaddingTop(link);
        element.innerHTML = iframe;
    }
}

function restore(element, playClass) {
    let img = element.getAttribute('data-img') || false;
    if (img) {
        element.classList.remove(playClass);
        element.removeAttribute('style');
        element.innerHTML = `<img src="${img}" />`;
        replace(element, 'a');
    }
}

function init(selector, playClass, links) {
    noAutoplay(() => {
        if (!links) {
            links = document.querySelectorAll(selector);
        }
        for (let i = links.length - 1; i >= 0; i--) {
            write(links[i], playClass);
        }
    });
}

function lightbox(type) {
    const SELECTOR = `a.${BASE}--${type}.${BASE}--lightbox`;

    lightboxHelper.init(SELECTOR, function(event) {
        let html = markup(this);
        if (html) {
            let paddingTop = getPaddingTop(this);
            event.preventDefault();
            lightboxHelper.get([type, 'iframe'], paddingTop).innerHTML = html;
            lightboxHelper.show();
        }
    });
}

function embed(type) {
    const SELECTOR = `a.${BASE}--${type}.${BASE}--inline`;
    const PLAY_CLASS = `${BASE}--play`;

    window.addEventListener('load', () => {
        init(SELECTOR, PLAY_CLASS);
    });
    addEvent(SELECTOR, function(event) {
        event.preventDefault();
        write(this, PLAY_CLASS);
    });
}

export { markup, replace, write, restore, init, lightbox, embed };
