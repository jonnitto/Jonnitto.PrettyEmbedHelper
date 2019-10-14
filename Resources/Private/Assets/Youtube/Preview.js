import { hasAutoplay } from '../Helper/checkAgent';

function fixPreview(img) {
    const IMAGE = img.getAttribute('src');
    if (
        IMAGE.naturalHeight <= 90 &&
        IMAGE.naturalWidth <= 120 &&
        src.indexOf('/default.jpg') == -1
    ) {
        src = src
            .replace('mqdefault', 'default')
            .replace('hqdefault', 'mqdefault')
            .replace('sddefault', 'hqdefault')
            .replace('maxresdefault', 'sddefault');
        IMAGE.setAttribute('src', src);
        setTimeout(() => {
            IMAGE.onload = () => {
                fixPreview(IMAGE);
            };
        }, 10);
        setTimeout(() => {
            fixPreview(IMAGE);
        }, 5000);
    }
}

function fixPreviews(images) {
    hasAutoplay(() => {
        if (typeof images === 'undefined') {
            images = document.querySelectorAll(
                'img.jonnitto-prettyembed__youtube-preview'
            );
        }
        for (let i = images.length - 1; i >= 0; i--) {
            fixPreview(images[i]);
        }
    });
}

window.addEventListener('load', () => {
    fixPreviews();
});

export default fixPreviews;
