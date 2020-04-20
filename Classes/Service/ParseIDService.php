<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;


/**
 * @Flow\Scope("singleton")
 */
class ParseIDService
{

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
        return $url;
    }

    /**
     * Get Youtube video id from url
     *
     * @param string|integer $url The URL od the plain id
     * @return string|null The video id extracted from url
     */

    public static function youtube($url = null)
    {
        if (!$url) {
            return null;
        }
        $regs = array();
        $url = trim(strval($url));
        if (preg_match('/(?<=(?:(?<=v)|(?<=i)|(?<=list))=)[a-zA-Z0-9-]+(?=&)|(?<=(?:(?<=v)|(?<=i)|(?<=list))\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:(?<=v)|(?<=i)|(?<=list))=)[^&\n]+|(?<=youtu.be\/)[^&\n]+/im', $url, $regs)) {
            return $regs[0];
        }
        return $url;
    }
}
