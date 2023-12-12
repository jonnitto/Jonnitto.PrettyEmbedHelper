export default function (Alpine) {
    Alpine.magic('prettyembedPause', () => pause);

    Alpine.magic('prettyembedReset', () => reset);
}

window.addEventListener('prettyembedReset', ({ detail }) => reset(detail));
window.addEventListener('prettyembedPause', ({ detail }) => pause(detail));

function reset(subject) {
    return getElements(subject).forEach((element) => {
        const data = Alpine.$data(element);
        if (typeof data.reset === 'function') {
            data.reset();
        }
    });
}

function pause(subject) {
    return getElements(subject).forEach((element) => {
        const data = Alpine.$data(element);
        if (typeof data.pause === 'function') {
            // the true is to not skip the autoplay check
            data.pause(true);
        }
    });
}

const className = 'jonnitto-prettyembed';
function getElements(subject) {
    if (typeof subject === 'string') {
        const selector = shortcutSelector(subject);
        let elements = [];
        [...document.querySelectorAll(selector)].forEach((item) => {
            elements = [...elements, ...findPlayers(item)];
        });
        return elements;
    }

    return findPlayers(subject);
}

const selectors = ['youtube', 'vimeo', 'video', 'audio'];
function shortcutSelector(selector) {
    const lower = selector.toLowerCase();
    if (selectors.includes(lower)) {
        return `.${className}--${lower}`;
    }
    return selector;
}

function findPlayers(subject) {
    if (!(subject instanceof Element)) {
        subject = document;
    } else if (subject.classList.contains(className)) {
        return [subject];
    }
    return [...subject.querySelectorAll(`.${className}`)];
}
