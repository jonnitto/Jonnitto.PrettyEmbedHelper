export default function (Alpine) {
    Alpine.magic('prettyembedPause', () => pause);
    Alpine.magic('prettyembedReset', () => reset);
    Alpine.magic('prettyembedPlay', () => play);
}

window.addEventListener('prettyembedReset', ({ detail }) => reset(detail));
window.addEventListener('prettyembedPause', ({ detail }) => pause(detail));
window.addEventListener('prettyembedPlay', ({ detail }) => play(detail));

function reset(subject) {
    return getElements(subject).forEach((element) => {
        const data = window.Alpine.$data(element);
        if (typeof data.reset === 'function') {
            data.reset();
        }
    });
}

function pause(subject) {
    return getElements(subject).forEach((element) => {
        const data = window.Alpine.$data(element);
        if (typeof data.pause === 'function') {
            // the true is to not skip the autoplay check
            data.pause(true);
        }
    });
}

function play(subject) {
    return getElements(subject).every((element) => {
        const data = window.Alpine.$data(element);
        if (typeof data.play === 'function') {
            data.play();
            // This cancels the loop
            return false;
        }
        // This continues the loop
        return true;
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
