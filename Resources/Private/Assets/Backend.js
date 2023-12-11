// Disable clicks in backend

let firstRun = true;

if (firstRun && window.name == 'neos-content-main') {
    firstRun = false;
    const element = document.createElement('style');
    element.setAttribute('rel', 'stylesheet');
    element.innerText = '.jonnitto-prettyembed-button{pointer-events:none !important}';
    document.head.appendChild(element);
}

// Store state of checkbox
const localSotrage = window.localStorage;
const storageKeyPrefix = 'prettyembedbackend';

[...document.querySelectorAll('.jonnitto-prettyembedbackend__checkbox')].forEach((element) => {
    const key = `${storageKeyPrefix}-${element.id}`
    if (localSotrage.getItem(key) === 'true') {
        element.checked = true;
    }

    element.addEventListener('change', () => {
        localSotrage.setItem(key, element.checked);
    })
});
