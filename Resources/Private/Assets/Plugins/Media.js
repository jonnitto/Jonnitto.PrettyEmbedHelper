import { eventName, playClass, loadScript } from './Helper';

const hlsScript = '/_Resources/Static/Packages/Jonnitto.PrettyEmbedHelper/Scripts/Hls.js?v=2';

export default function (Alpine) {
    Alpine.directive('prettyembedmedia', (element, { value, modifiers, expression }, { evaluate }) => {
        if (value === 'media') {
            let options = {
                slim: modifiers.includes('slim'),
            };

            // Check if expression is an object
            if (expression.startsWith('{') && expression.endsWith('{')) {
                options = { ...options, ...evaluate(expression || '{}') };
            } else {
                options.src = expression;
            }
            handleMedia({ element, Alpine, options });
            return;
        }

        const streaming = modifiers.includes('streaming');
        const style = modifiers.includes('lightbox') ? 'lightbox' : 'inline';
        const options = { streaming, style, ...evaluate(expression || '{}') };
        handleRoot({ element, Alpine, options });
    });
}

function handleRoot({ element, Alpine, options }) {
    const { style, streaming } = options;
    let data = {
        type: null,
        playing: false,
        url: null,
        id: null,
        autoplay: false,
        loaded: false,
        lightbox: style === 'lightbox' ? false : null,
    };

    const needHlsWrapper = streaming ? !streamcheck() : null;

    Alpine.bind(element, {
        ':class'() {
            return this.playing && playClass;
        },
        'x-data'() {
            return {
                ...data,
                play() {
                    if (this.lightbox !== null) {
                        this.lightbox = true;
                    }

                    if (!this.loaded && needHlsWrapper) {
                        if (this.lightbox) {
                            setTimeout(() => {
                                handleStreaming(this.__media, this.url);
                            }, 500);
                            return;
                        }
                        handleStreaming(this.__media, this.url);
                        return;
                    }

                    if (this.lightbox) {
                        setTimeout(() => {
                            this.__media?.play();
                        }, 500);
                        return;
                    }

                    this.__media?.play();
                },
                pause(skipAutoplay = false) {
                    if (skipAutoplay && (this.autoplay || this.__media?.muted)) {
                        return;
                    }
                    this.__media?.pause();
                },
                reset() {
                    if (this.autoplay) {
                        return;
                    }
                    this.pause();
                    this.loaded = false;
                    if (this.__media?.currentTime) {
                        this.__media.currentTime = 0;
                    }
                    if (this.type === 'Video') {
                        this.__media.removeAttribute('controls');
                        this.__media.load();
                    }
                },
                toogle() {
                    if (this.__media.paused) {
                        this.play();
                        return;
                    }
                    this.pause();
                },
                dispatchEvent(event) {
                    const currentTime = this.__media.currentTime;
                    if (currentTime === this.__media.duration) {
                        event = 'finished';
                    }
                    this.$dispatch(eventName, {
                        detail: {
                            event,
                            currentTime,
                            type: this.type,
                            style,
                            autoplay: this.autoplay,
                            url: this.url,
                            id: this.id,
                        },
                    });
                },
                init() {
                    if (style === 'lightbox') {
                        this.$watch('lightbox', (value, oldValue) => {
                            if (!value && value !== oldValue) {
                                this.pause();
                            }
                        });
                    }
                },
                __media: null,
            };
        },
        '@prettyEmbedReset.window'() {
            this.reset();
        },
        '@prettyEmbedPause.window'({ detail }) {
            // detail is the rootElement
            if (detail == this.$root) {
                return;
            }
            this.pause(true);
        },
    });
}

function handleMedia({ element, Alpine, options }) {
    const { slim, src } = options;
    const isVideo = element.tagName.toLowerCase() === 'video';
    const type = isVideo ? 'Video' : 'Audio';

    Alpine.bind(element, {
        'x-init'() {
            this.__media = element;
            this.url = src || element.currentSrc;
            this.id = this.url.split('/').pop();
            this.autoplay = element.autoplay;
            this.type = type;
        },
        '@play'() {
            if (!this.loaded) {
                this.loaded = true;
                if (!slim) {
                    element.setAttribute('controls', true);
                }
            }
            this.playing = true;
            if (!this.autoplay && !this.muted) {
                this.$dispatch('prettyEmbedPause', this.$root);
                this.dispatchEvent('play');
            }
        },
        '@pause'() {
            this.playing = false;
            this.autoplay = false;
            if (!this.muted) {
                this.dispatchEvent('pause');
            }
        },
    });
}

function streamcheck() {
    const element = document.createElement('video');
    return !!element.canPlayType('application/vnd.apple.mpegurl');
}

function handleStreaming(element, src) {
    if (typeof Hls !== 'undefined') {
        loadHls(element, src);
        return;
    }
    loadScript(hlsScript, () => {
        setTimeout(() => loadHls(element, src), 10);
    });
}

function loadHls(element, src) {
    if (!Hls.isSupported()) {
        return;
    }
    const liveStreaming = new Hls();
    liveStreaming.loadSource(src);
    liveStreaming.attachMedia(element);
    liveStreaming.on(Hls.Events.MEDIA_ATTACHED, () => element.play());
}
