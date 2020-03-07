<?php

namespace Jonnitto\PrettyEmbedHelper\Eel;


use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

/**
 * @Flow\Proxy(false)
 */
class Helper implements ProtectedContextAwareInterface
{

    /**
     * This helper calcualtes the padding from a given ratio or width and height
     *
     * @param float||string $ratio
     * @param integer $width
     * @param integer $height
     * @return string The calculated value
     */
    function paddingTop($ratio = null, int $width = null, int $height = null)
    {
        if ($ratio && is_string($ratio)) {
            return $ratio;
        }

        if ($ratio && is_float($ratio) || is_int($ratio)) {
            return (100 / $ratio) . '%';
        }

        if ($width && $height && is_int($width) && is_int($height)) {
            return (100 / ($width / $height)) . '%';
        }

        return null;
    }



    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
