<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class OembedService
{
    /**
     * Grab the data of a publicly embeddable video hosted on youtube
     * 
     * @param string|integer $id The "id" of a video
     * @param string $type
     * @param string $apiKey for fetching the duration
     * @return mixed The data or null if there's an error
     */
    public static function youtube($id, string $type = 'video', ?string $apiKey = null)
    {
        if (!$id || ($type !== 'video' && $type !== 'playlist')) {
            return null;
        }
        $pathAndQuery = $type === 'video' ? "watch?v={$id}" : "playlist?list={$id}";
        $url = urlencode("https://youtube.com/{$pathAndQuery}");
        $data = json_decode(@file_get_contents("https://www.youtube.com/oembed?url={$url}"));

        if (!$data) {
            return null;
        }

        if ($apiKey) {
            if ($type === 'video') {
                $data->duration = self::getDurationFromYoutubeVideo($id, $apiKey);
            } else {
                $videos = self::getVideosFromYoutubePlaylist($id, $apiKey);
                $duration = 0;
                foreach ($videos as $video) {
                    $duration += self::getDurationFromYoutubeVideo($video, $apiKey);
                }
                $data->duration = $duration;
            }
        }

        return $data;
    }

    /**
     * Grab the data of a publicly embeddable video hosted on vimeo
     * 
     * @param string|integer $id The "id" of a video
     * @return mixed The data or null if there's an error
     */
    public static function vimeo($id)
    {
        if (!$id) {
            return null;
        }

        $url = urlencode("https://vimeo.com/{$id}");
        $data = json_decode(@file_get_contents("https://vimeo.com/api/oembed.json?url={$url}&width=2560"));

        return $data ?? null;
    }

    /**
     * Remove the prototcol from a url and replace it with `//`
     *
     * @param string $url
     * @return mixed
     */
    public static function removeProtocolFromUrl(?string $url = null)
    {
        if (!is_string($url)) {
            return null;
        }
        return preg_replace('/https?:\/\//i', '//', $url);
    }

    /**
     * Get video ids from youtube playlist
     * 
     * @param string $id
     * @param string $apiKey
     * @return array
     */
    protected static function getVideosFromYoutubePlaylist(string $id, string $apiKey): array
    {
        $videos = [];
        $data = json_decode(@file_get_contents("https://www.googleapis.com/youtube/v3/playlistItems?part=contentDetails&playlistId={$id}&key={$apiKey}"), true);
        foreach ($data['items'] as $item) {
            $videos[] = $item['contentDetails']['videoId'];
        }
        return $videos;
    }

    /**
     * Get duration of an youtube video
     *
     * @param string $id
     * @param string $apiKey
     * @return integer
     */
    protected static function getDurationFromYoutubeVideo(string $id, string $apiKey): int
    {
        $data = json_decode(@file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id={$id}&key={$apiKey}"), true);
        $duration = $data['items'][0]['contentDetails']['duration'];
        $start = new \DateTime('@0'); // Unix epoch
        $start->add(new \DateInterval($duration));
        return $start->getTimestamp();
    }
}
