<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;


/**
 * @Flow\Scope("singleton")
 */
class ParseIDService
{
    /**
     * Get the type of videoplatform
     *
     * @param string|integer $url The URL or the plain id
     * @return string|null The platform from the given url
     */
    public static function platform($url = null): ?string
    {
        $url = trim(strval($url));
        if (!$url) {
            return null;
        }

        if (strpos($url, 'vimeo.com') !== false) {
            return 'vimeo';
        }

        if (strpos($url, 'youtu.be') !== false || strpos($url, 'youtube')) {
            return 'youtube';
        }

        $isCompleteUrl = preg_match('/^https?:\/\//im', $url);
        if (!$isCompleteUrl) {
            // Vimeo has only numbers
            if (preg_match('/^\d+$/', $url)) {
                return 'vimeo';
            }

            // The ID has to start with a letter / number / _
            if (preg_match('/^[_A-Za-z0-9]/', $url)) {
                return 'youtube';
            }
        }

        return null;
    }

    /**
     * Get the type of a youtube video
     *
     * @param string $url
     * @return string|null The type of the link
     */
    public static function youtubeType(string $url): ?string
    {
        $url = trim(strval($url));
        if (!$url) {
            return null;
        }

        return strpos($url, 'list=') !== false ? 'playlist' : 'video';
    }

    /**
     * Get Vimeo video id from url
     *
     * Supported url formats
     *
     * https://vimeo.com/11111111
     * http://vimeo.com/11111111
     * https://www.vimeo.com/11111111
     * http://www.vimeo.com/11111111
     * https://vimeo.com/channels/11111111
     * http://vimeo.com/channels/11111111
     * https://vimeo.com/groups/name/videos/11111111
     * http://vimeo.com/groups/name/videos/11111111
     * https://vimeo.com/album/2222222/video/11111111
     * http://vimeo.com/album/2222222/video/11111111
     * https://vimeo.com/11111111?param=test
     * http://vimeo.com/11111111?param=test
     *
     * @param string|integer $url The URL or the plain id
     * @return string|null The video id extracted from url
     */

    public static function vimeo($url = null): ?string
    {
        if (!$url) {
            return null;
        }
        $regs = array();
        $url = trim(strval($url));
        if (preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $regs)) {
            return $regs[3];
        }
        // The ID has to start with an number
        if (preg_match('/^[0-9]/', $url)) {
            return $url;
        }
        return null;
    }

    /**
     * Get Youtube video id from url
     * 
     * Supported url formats
     *
     * https://youtu.be/IdOfTheVideo
     * https://www.youtube.com/embed/IdOfTheVideo
     * youtu.be/IdOfTheVideo
     * youtube.com/watch?v=IdOfTheVideo
     * http://youtu.be/IdOfTheVideo&t=2m
     * http://www.youtube.com/embed/IdOfTheVideo&t=2m5s
     * http://www.youtube.com/watch?v=IdOfTheVideo
     * http://www.youtube.com/watch?v=IdOfTheVideo&feature=g-vrec&t=30s
     * http://www.youtube.com/watch?v=IdOfTheVideo&feature=player_embedded
     * http://www.youtube.com/v/IdOfTheVideo?fs=1&hl=en_US
     * http://www.youtube.com/ytscreeningroom?v=IdOfTheVideo
     * http://www.youtube.com/watch?NR=1&feature=endscreen&v=IdOfTheVideo
     * http://www.youtube.com/user/Scobleizer#p/u/1/1p3vcRhsYGo
     * http://www.youtube.com/watch?v=IdOfTheVideo&feature=c4-overview-vl&list=PlaylistID
     * https://www.youtube.com/watch?v=IdOfTheVideo&list=PlaylistID
     *
     * @param string|integer $url The URL or the plain id
     * @return string|null The video id extracted from url
     */

    public static function youtube($url = null, string $type = 'video'): ?string
    {
        if (!$url) {
            return null;
        }
        $regs = array();
        $url = trim(strval($url));

        if (preg_match_all('/(?<=(?:(?<=v)|(?<=i)|(?<=list))=)[a-zA-Z0-9-]+(?=&)|(?<=(?:(?<=v)|(?<=i)|(?<=list))\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:(?<=v)|(?<=i)|(?<=list))=)[^&\n]+|(?<=youtu.be\/)[^&\n]+/im', $url, $regs)) {
            $array = $regs[0];

            if (count($array) == 1) {
                return $array[0];
            }

            $returnKey = 0;
            // Playlist have always longer IDs
            if (strcmp($array[0], $array[1])) {
                // String 2 is longer
                $returnKey = $type === 'video' ? 0 : 1;
            } else {
                $returnKey = $type === 'video' ? 1 : 0;
            }
            return $array[$returnKey];
        }
        // The ID has to start with a letter / number / _ / -
        if (preg_match('/^[A-Za-z0-9_-]/', $url)) {
            return $url;
        }
        return null;
    }
}
