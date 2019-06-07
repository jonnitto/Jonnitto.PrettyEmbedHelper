import Gator from 'gator';
import { noAutoplay } from './checkAgent';
import * as lightboxHelper from '../Helper/Lightbox';

const BASE = 'jonnitto-prettyembed';

function markup(node) {
    let fullscreen =
        node.getAttribute('data-fs') == 'true' ? ' allowfullscreen' : '';
    let embed = node.getAttribute('data-embed') || false;

    if (!embed) {
        return false;
    }

    return `<div class="${BASE}__lightbox-holder"><iframe class="${BASE}__lightbox-iframe" src="${src}" frameborder="0" allow="fullscreen; accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"${fullscreen}></iframe></div>`;
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

function write(link, playClass) {
    let embed = link.getAttribute('data-embed') || false;
    let image = link.getElementsByTagName('img')[0];
    let imageSrc = image.getAttribute('src') || false;
    let width = image.width;
    let height = image.height;
    if (embed && width && height) {
        let element = replace(link, 'div');
        let fullscreen =
            link.getAttribute('data-fs') == 'true' ? 'allowfullscreen ' : '';

        element.setAttribute('data-img', imageSrc);
        element.classList.add(playClass);
        element.style.paddingTop =
            (parseInt(height) / parseInt(width)) * 100 + '%';
        element.innerHTML = `<iframe src="${embed}" width="${width}" height="${height}" ${fullscreen}frameborder="0" allow="fullscreen; accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"></iframe>`;
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

function init(selector, links) {
    noAutoplay(() => {
        if (!links) {
            links = document.querySelectorAll(selector);
        }
        for (let i = links.length - 1; i >= 0; i--) {
            write(links[i]);
        }
    });
}

function lightbox(type) {
    const SELECTOR = `a.${BASE}__${type}--lightbox`;

    lightboxHelper.init(SELECTOR, function(event) {
        let html = markup(embed, fullscreen);
        if (html) {
            event.preventDefault();
            lightboxHelper.get().innerHTML = html;
            lightboxHelper.show();
        }
    });
}

function embed(type) {
    const SELECTOR = `a.${BASE}__${type}--inline`;
    const PLAY_CLASS = `${BASE}__${type}--play`;

    Gator(window).on('load', function() {
        init(SELECTOR);
    });
    Gator(document.documentElement).on('click', SELECTOR, function(event) {
        event.preventDefault();
        write(this, PLAY_CLASS);
    });
}

export { markup, replace, write, restore, init, lightbox, embed };
