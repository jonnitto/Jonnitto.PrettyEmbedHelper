<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Neos\Flow\Annotations as Flow;
use function preg_replace;

/**
 * @Flow\Scope("singleton")
 */
class Utility
{
    /**
     * Remove the protocol from url and replace it with `//`
     *
     * @param string|null $url
     * @return string|null
     */
    public function removeProtocolFromUrl(?string $url = null): ?string
    {
        if (!is_string($url)) {
            return null;
        }
        return preg_replace('/https?:\/\//i', '//', $url);
    }

    /**
     * This calculates the padding-top from width and height
     *
     * @param integer $width
     * @param integer $height
     * @return string The calculated value
     */
    public function calculatePaddingTop(int $width, int $height): string
    {
        return 100 / ($width / $height) . '%';
    }
}
