<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class ApiService
{

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        $this->browser = new Browser();
        $this->browser->setRequestEngine(new CurlEngine());
    }

    /**
     * Grab the data of a publicly embeddable video hosted on youtube
     * 
     * @param string|integer $id The "id" of a video
     * @param string $type
     * @param string $apiKey for fetching the duration
     * @return array|null The data or null if there's an error
     */
    public function youtube(
        $id,
        string $type = 'video',
        ?string $apiKey = null
    ): ?array {
        if (!$id || ($type !== 'video' && $type !== 'playlist')) {
            return null;
        }
        if ($apiKey) {
            $data = $this->getDataFromYoutubeVideoWithApi($id, $apiKey, $type);
            if ($data) {
                return $data;
            }
        }
        $pathAndQuery = $type === 'video' ? "watch?v={$id}" : "playlist?list={$id}";
        $url = \urlencode("https://youtube.com/{$pathAndQuery}");
        $data = $this->getJson(
            'https://www.youtube.com/oembed?url=' . $url,
            $type,
            'YouTube Oembed Service',
            $id
        );

        return $data ?? null;
    }

    /**
     * Grab the data of a publicly embeddable video hosted on vimeo
     * 
     * @param string|integer $id The "id" of a video
     * @return array|null The data or null if there's an error
     */
    public function vimeo($id): ?array
    {
        if (!$id) {
            return null;
        }

        $url = \urlencode("https://vimeo.com/{$id}");
        $data = $this->getJson(
            'https://vimeo.com/api/oembed.json?width=2560&url=' . $url,
            'video',
            'Vimeo Oembed Service',
            $id
        );
        return $data ?? null;
    }

    /**
     * Get json from url
     *
     * @param string $url
     * @return array|null The data or null if there's an error
     */
    protected function getJson(
        string $url,
        string $type,
        string $service,
        string $id
    ): ?array {
        $message = sprintf(
            '"%s data from %s" with the id "%s"',
            $type,
            $service,
            $id
        );
        $request = $this->browser->request($url);
        if ($request->getReasonPhrase() !== 'OK') {
            $code = $request->getStatusCode();
            $this->logger->error(
                sprintf(
                    'The request for %s failed with the status code "%s"',
                    $message,
                    $code
                ),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return null;
        }
        $content = $request->getBody()->getContents();
        $data = \json_decode($content, true);
        if ($service === 'Google API Service' && (!isset($data['pageInfo']) || $data['pageInfo']['totalResults'] === 0)) {
            $this->logger->error(
                "Error while get $message. Returned data: " . \json_encode($data),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return null;
        }

        $this->logger->debug(
            "Load $message",
            LogEnvironment::fromMethodName(__METHOD__)
        );

        return $data;
    }

    /**
     * Convert an ISO8601 duration to seconds
     *
     * @param string|null  $ISO8601duration
     * @return integer
     */
    protected function convertToSeconds(?string $ISO8601duration = null): int
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
     * @return array|null The array with the data or null if there's an error
     */
    protected function makeCallToGoogleApi(
        string $id,
        string $apiKey,
        string $type
    ): ?array {
        $url = "https://www.googleapis.com/youtube/v3/{$type}s?key={$apiKey}&part=contentDetails";
        $typeForLogger = $type == 'playlistItem' ? 'playlist items' : $type;

        if ($type != 'playlistItem') {
            $url .= ',snippet,player&id=';
        } else {
            $url .= '&playlistId=';
        }
        $data = $this->getJson(
            $url . $id,
            $typeForLogger,
            'Google API Service',
            $id
        );
        return $data ? $data['items'] : null;
    }

    /**
     * Get the video data using the youtube api
     *
     * @param string $id
     * @param string $apiKey
     * @param string $type
     * @return array|null The array with the data or null if there's an error
     */
    protected function getDataFromYoutubeVideoWithApi(
        string $id,
        string $apiKey,
        string $type
    ): ?array {
        $data = $this->makeCallToGoogleApi($id, $apiKey, $type);
        if (!$data) {
            return null;
        }
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
            $duration = $this->convertToSeconds($item['contentDetails']['duration']);
        } else {
            // From a playlist, get every video ID and read the duration
            $playlistItems = $this->makeCallToGoogleApi(
                $id,
                $apiKey,
                'playlistItem'
            );
            foreach ($playlistItems as $playlistItem) {
                $videoId = $playlistItem['contentDetails']['videoId'];
                $videoEntry = $this->makeCallToGoogleApi($videoId, $apiKey, 'video');
                $duration += $this->convertToSeconds(
                    $videoEntry[0]['contentDetails']['duration']
                );
            }
        }

        return [
            'title' => $title,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'imageUrl' => $imageUrl,
            'imageResolution' => $imageResolution,
        ];
    }
}
