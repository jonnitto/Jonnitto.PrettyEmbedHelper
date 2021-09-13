<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use DateInterval;
use DateTimeImmutable;
use DOMDocument;
use Exception;
use JsonException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;
use function json_decode;
use function json_encode;
use function urlencode;

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

    /**
     * @var Browser
     */
    protected $browser;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->browser = new Browser();
        $this->browser->setRequestEngine(new CurlEngine());
    }

    /**
     * Grab the data of a publicly embeddable video hosted on YouTube
     *
     * @param string|integer $id The "id" of a video
     * @param string $type
     * @param string|null $apiKey for fetching the duration
     * @return array|null The data or null if there's an error
     * @throws InfiniteRedirectionException|JsonException
     * @throws Exception
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
        $pathAndQuery = ($type === 'video' ? 'watch?v=' : 'playlist?list=') . $id;
        $url = urlencode('https://youtube.com/' . $pathAndQuery);
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
     * @param string|null $id The "id" of a video
     * @return array|null The data or null if there's an error
     * @throws InfiniteRedirectionException
     * @throws JsonException
     */
    public function vimeo(?string $id = null): ?array
    {
        if (!$id) {
            return null;
        }

        if (!strpos($id, 'https://vimeo.com/')) {
            $id = str_replace('https://vimeo.com/', '', $id);
        }

        $url = urlencode('https://vimeo.com/' . $id);

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
     * @param string $type
     * @param string $service
     * @param string $id
     * @return array|null The data or null if there's an error
     * @throws InfiniteRedirectionException
     * @throws JsonException
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
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if ($service === 'Google API Service' && (!isset($data['pageInfo']) || $data['pageInfo']['totalResults'] === 0)) {
            $this->logger->error(
                "Error while get $message. Returned data: " . json_encode($data, JSON_THROW_ON_ERROR),
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
     * @param string|null $ISO8601duration
     * @return integer
     * @throws Exception
     */
    protected function convertToSeconds(?string $ISO8601duration = null): int
    {
        if (!$ISO8601duration) {
            return 0;
        }
        $interval = new DateInterval($ISO8601duration);
        $ref = new DateTimeImmutable;
        return $ref->add($interval)->getTimestamp() - $ref->getTimestamp();
    }

    /**
     * Make the call the YouTube V3 API
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
        $url = sprintf('https://www.googleapis.com/youtube/v3/%ss?key=%s&part=contentDetails', $type, $apiKey);
        $typeForLogger = $type === 'playlistItem' ? 'playlist items' : $type;

        if ($type !== 'playlistItem') {
            $url .= ',snippet,player&id=';
        } else {
            $url .= '&playlistId=';
        }
        try {
            $data = $this->getJson(
                $url . $id,
                $typeForLogger,
                'Google API Service',
                $id
            );
        } catch (JsonException | InfiniteRedirectionException $e) {
        }
        return isset($data) ? $data['items'] : null;
    }

    /**
     * Get the video data using the YouTube api
     *
     * @param string $id
     * @param string $apiKey
     * @param string $type
     * @return array|null The array with the data or null if there's an error
     * @throws Exception
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
        $dom = new DOMDocument();
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
        if ($type === 'video') {
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
