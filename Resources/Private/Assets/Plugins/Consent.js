export default function (Alpine) {
    Alpine.directive('prettyembedconsent', (element, { value }) => {
        if (value === 'accept') {
            handleAccept({ element, Alpine });
            return;
        }
        handleRoot({ element, Alpine });
    });
}

function handleRoot({ element, Alpine }) {
    Alpine.bind(element, {
        'x-show'() {
            return this.gdpr == 'isOpen';
        },
        'x-transition.opacity'() {},
    });
}

function handleAccept({ element, Alpine }) {
    Alpine.bind(element, {
        '@click'(event) {
            event.preventDefault();
            this.acceptGdpr();
        },
    });
}
