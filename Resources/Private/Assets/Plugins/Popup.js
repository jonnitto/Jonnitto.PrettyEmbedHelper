function onClick(ratio, href) {
    const width = Math.min(window.innerWidth, 1260);
    const height = width / ratio;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;
    window.open(
        href,
        '_blank',
        `noopener=yes,directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=${width},height=${height},left=${left},top=${top}`,
    );
}

export default function (Alpine) {
    Alpine.directive('prettyembedpopup', (element, { modifiers, expression }, { evaluate }) => {
        const ratioPropertyValue = window.getComputedStyle(element).getPropertyValue('--aspect-ratio') || '16 / 9';
        const { ratio } = evaluate(`{ratio:${ratioPropertyValue}}`);
        const elementHref = element.href;
        const hrefExpression = modifiers.includes('dynamic');

        Alpine.bind(element, {
            '@click'(event) {
                event.preventDefault();
                const href = (hrefExpression ? evaluate(expression) : expression) || elementHref;
                onClick(ratio, href);
            },
        });
    });
}
