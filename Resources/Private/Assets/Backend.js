// Disable clicks in backend

let firstRun = true;

if (firstRun && window.name == 'neos-content-main') {
    firstRun = false;
    const element = document.createElement('style');
    element.setAttribute('rel', 'stylesheet');
    element.innerText = '.jonnitto-prettyembed-button{pointer-events:none !important}';
    document.head.appendChild(element);
}
