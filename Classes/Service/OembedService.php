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
        if ($apiKey) {
            return self::getDataFromYoutubeVideoWithApi($id, $apiKey, $type);
        }

        $pathAndQuery = $type === 'video' ? "watch?v={$id}" : "playlist?list={$id}";
        $url = \urlencode("https://youtube.com/{$pathAndQuery}");
        $data = \json_decode(@file_get_contents("https://www.youtube.com/oembed?url={$url}"));

        return $data ?? null;
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

        $url = \urlencode("https://vimeo.com/{$id}");
        $data = \json_decode(@file_get_contents("https://vimeo.com/api/oembed.json?url={$url}&width=2560"));

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
     * Convert an ISO8601 duration to seconds
     *
     * @param string|null  $ISO8601duration
     * @return integer
     */
    protected static function convertToSeconds(?string $ISO8601duration = null): int
    {
        if (!$ISO8601duration) {
            return 0;
        }
        $interval = new \DateInterval($ISO8601duration);
        $ref = new \DateTimeImmutable;
        return $ref->add($interval)->getTimestamp() - $ref->getTimestamp();
    }

    /**
     * Make the call the the YouTube V3 API
     *
     * @param string $id
     * @param string $apiKey
     * @param string $type
     * @return mixed
     */
    protected static function makeCallToApi(string $id, string $apiKey, string $type)
    {
        $url = "https://www.googleapis.com/youtube/v3/{$type}s?key={$apiKey}&part=contentDetails";

        if ($type != 'playlistItem') {
            $url .= ',snippet,player&id=';
        } else {
            $url .= '&playlistId=';
        }
        $data =  \json_decode(@file_get_contents($url . $id), true);
        return $data['items'];
    }

    /**
     * Get the video data using the youtube api
     *
     * @param string $id
     * @param string $apiKey
     * @param string $type
     * @return object
     */
    protected static function getDataFromYoutubeVideoWithApi(string $id, string $apiKey, string $type): object
    {
        $data = self::makeCallToApi($id, $apiKey, $type);
        $item = $data[0];

        // Get the title
        $title = $item['snippet']['title'];

        // Get the dimensions
        $dom = new \DOMDocument();
        $dom->loadHTML($item['player']['embedHtml']);
        $iframe = $dom->getElementsByTagName('iframe')[0];
        $width = $iframe ? (int) $iframe->getAttribute("width") : null;
        $height = $iframe ? (int) $iframe->getAttribute("height") : null;

        // Get the best possible image
        $thumbnail = end($item['snippet']['thumbnails']);
        $imageUrl = $thumbnail['url'];
        $imageResolution = $imageUrl ? explode('.', basename($imageUrl))[0] : null;

        // Get the duration
        $duration = 0;
        if ($type == 'video') {
            // From a single video
            $duration = self::convertToSeconds($item['contentDetails']['duration']);
        } else {
            // From a playlist, get every video ID and read the duration
            $playlistItems = self::makeCallToApi($id, $apiKey, 'playlistItem');
            foreach ($playlistItems as $playlistItem) {
                $videoId = $playlistItem['contentDetails']['videoId'];
                $videoEntry = self::makeCallToApi($videoId, $apiKey, 'video');
                $duration += self::convertToSeconds($videoEntry[0]['contentDetails']['duration']);
            }
        }

        return (object) [
            'title' => $title,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'imageUrl' => $imageUrl,
            'imageResolution' => $imageResolution,
        ];
    }
}
