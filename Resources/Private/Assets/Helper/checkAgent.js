function check(autoplay = true, callback) {
    const HAS_AUTOPLAY = !/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(
        window.navigator.userAgent
    );
    if ((!HAS_AUTOPLAY && !autoplay) || (HAS_AUTOPLAY && autoplay)) {
        if (typeof callback == 'function') {
            callback();
        }
        return true;
    }
    return false;
}

function hasAutoplay(callback) {
    check(true, callback);
}

function noAutoplay(callback) {
    check(false, callback);
}

export { hasAutoplay, noAutoplay };
