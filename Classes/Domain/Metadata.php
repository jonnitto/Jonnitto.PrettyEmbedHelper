<?php

namespace Jonnitto\PrettyEmbedHelper\Domain;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\ImageInterface;
use JsonSerializable;

/**
 * @Flow\Proxy(false)
 */
final class Metadata implements JsonSerializable
{

    private string|null $videoID;
    private string|null $title;
    private string|null $aspectRatio;
    private int|null $duration;
    private string|null $image;
    private string|null $href;
    private string|null $embedHref;
    private ImageInterface|null $thumbnail;

    private function __construct(
        string|null $videoID,
        string|null $title,
        string|null $aspectRatio,
        int|null $duration,
        string|null $image,
        string|null $href,
        string|null $embedHref,
        ImageInterface|null $thumbnail
    ) {
        $this->videoID = $videoID;
        $this->title = $title;
        $this->aspectRatio = $aspectRatio;
        $this->duration = $duration;
        $this->image = $image;
        $this->href = $href;
        $this->embedHref = $embedHref;
        $this->thumbnail = $thumbnail;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setDuration(?int $duration = null): void
    {
        $this->duration = $duration;
    }

    /**
     * @param array $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array['videoID'],
            $array['title'],
            $array['aspectRatio'],
            $array['duration'],
            $array['image'],
            $array['href'],
            $array['embedHref'],
            $array['thumbnail'],
        );
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'videoID' => $this->videoID,
            'title' => $this->title,
            'aspectRatio' => $this->aspectRatio,
            'duration' => $this->duration,
            'image' => $this->image,
            'href' => $this->href,
            'embedHref' => $this->embedHref,
            'thumbnail' => $this->thumbnail,
        ];
    }
}
