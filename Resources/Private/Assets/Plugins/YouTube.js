import { loadYoutubeApi, checkFullscreen } from './Helper';

const eventName = 'prettyembed';

export default function (Alpine) {
    Alpine.directive('prettyembedyoutube', (element, { modifiers, expression }, { evaluate }) => {
        const style = modifiers.includes('lightbox') ? 'lightbox' : 'inline';
        const slim = modifiers.includes('slim');
        const options = { slim, style, ...evaluate(expression || '{}') };
        handeleRoot({ element, Alpine, options });
    });
}

function handeleRoot({ element, Alpine, options }) {
    const type = 'YouTube';
    let { video, playlist, style, slim, loop, noCookie } = options;

    const videoPlayerOptions = {
        playerVars: {
            autoplay: 1,
            modestbranding: 1,
            playsinline: 1,
            rel: 0,
            showinfo: 0,
            controls: slim ? 0 : 1,
            loop: loop ? 1 : 0,
        },
    };

    if (noCookie) {
        videoPlayerOptions.host = 'https://www.youtube-nocookie.com';
    }

    if (video) {
        videoPlayerOptions.videoId = video;
    }

    if (playlist) {
        if (!playlist.startsWith('PL')) {
            playlist = 'PL' + playlist;
        }
        videoPlayerOptions.playerVars.listType = 'playlist';
        videoPlayerOptions.playerVars.list = playlist;
    }

    const localStorage = window.localStorage;
    const storageKey = `jonnittoprettyembed_gdpr_${type.toLowerCase()}`;

    const data = {
        title: options.title,
        loaded: false,
        playing: false,
        lightbox: style === 'lightbox' ? false : null,
        // gdpr can be 'isAccepted', 'isOpen' or 'needCheck'
        gdpr: options.gdpr && localStorage.getItem(storageKey) !== 'true' ? 'needCheck' : 'isAccepted',
    };

    let player = null;

    Alpine.bind(element, {
        'x-data'() {
            return {
                ...data,
                init() {
                    if (this.ligtbox !== null) {
                        this.$watch('lightbox', (value, oldValue) => {
                            if (!value && value !== oldValue) {
                                this.pause();
                            }
                        });
                    }
                },
                acceptGdpr() {
                    localStorage.setItem(storageKey, 'true');
                    this.$dispatch('prettyembedAcceptGdpr', type);
                    this.load();
                },
                play() {
                    player?.playVideo();
                },
                pause() {
                    player?.pauseVideo();
                },
                load(event) {
                    if (event) {
                        event.preventDefault();
                    }
                    if (this.gdpr != 'isAccepted') {
                        this.gdpr = 'isOpen';
                        return;
                    }
                    if (this.lightbox !== null) {
                        this.lightbox = true;
                    }
                    if (this.loaded) {
                        this.play();
                        return;
                    }
                    loadYoutubeApi(() => {
                        const target = this.$refs?.youtube || element;
                        const YT = window.YT;
                        player = new YT.Player(target, {
                            ...videoPlayerOptions,
                            events: {
                                onStateChange: ({ data }) => {
                                    setTimeout(() => {
                                        if (data === YT.PlayerState.PLAYING) {
                                            this.$dispatch('prettyembedPauseInternal', element);
                                            this.loaded = true;
                                            this.playing = true;
                                            this.$dispatch(
                                                eventName,
                                                dispatchDetails({ event: 'play', type, style, player }),
                                            );
                                            return;
                                        }

                                        if (data === YT.PlayerState.PAUSED) {
                                            this.playing = false;
                                            this.$dispatch(
                                                eventName,
                                                dispatchDetails({ event: 'pause', type, style, player }),
                                            );
                                            return;
                                        }

                                        if (data === YT.PlayerState.ENDED) {
                                            this.playing = false;
                                            if (!loop && !this.lightbox && !checkFullscreen()) {
                                                this.reset();
                                            }
                                            this.$dispatch(
                                                eventName,
                                                dispatchDetails({ event: 'finished', type, style, player }),
                                            );
                                        }
                                    }, 10);
                                },
                            },
                        });
                    });
                },
                reset() {
                    if (!player) {
                        return;
                    }
                    player?.stopVideo();
                    player.destroy();
                    player = null;
                    this.loaded = false;
                    this.playing = false;
                },
            };
        },
        '@prettyembedAcceptGdpr.window'({ detail }) {
            // detail is the type who is accepted
            if (detail == type) {
                this.gdpr = 'isAccepted';
            }
        },
        '@prettyembedPauseInternal.window'({ detail }) {
            // detail is the rootElement
            if (detail == this.$root) {
                return;
            }
            this.pause();
        },
    });
}

function dispatchDetails({ player, type, style, event }) {
    let videoUrl;
    let currentTime = 0;
    let data = {};
    try {
        videoUrl = player?.getVideoUrl() || null;
        currentTime = player?.getCurrentTime() || 0;
        data = player?.getVideoData() || {};
    } catch (error) {} // eslint-disable-line

    return {
        event,
        type,
        style,
        title: data?.title,
        author: data?.author,
        videoUrl,
        currentTime,
        videoID: data?.video_id,
        quality: data?.video_quality,
        playlistID: data?.list || null,
    };
}
