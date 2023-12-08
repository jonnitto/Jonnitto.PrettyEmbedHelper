export default function (Alpine) {
    Alpine.magic('prettyEmbedPause', () => {
        return (subject) => {
            getElements(subject).forEach((element) => {
                const data = Alpine.$data(element);
                if (typeof data.pause === 'function') {
                    data.pause(true);
                }
            });
        };
    });

    Alpine.magic('prettyEmbedReset', () => {
        return (subject) => {
            getElements(subject).forEach((element) => {
                const data = Alpine.$data(element);
                if (typeof data.reset === 'function') {
                    data.reset();
                }
            });
        };
    });
}

const className = 'jonnitto-prettyembed';
function getElements(subject) {
    console.log({ subject });
    if (typeof subject === 'string') {
        const selector = shortcutSelector(subject);
        console.log({ selector });
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
