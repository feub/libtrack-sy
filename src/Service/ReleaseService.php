<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReleaseService
{
  private $coverDir;

  public function __construct(
    private ParameterBagInterface $params,
  ) {
    $this->coverDir = $params->get('cover_dir');
  }

  public function downloadCovertArt(string $coverUrl, string $mbid): string
  {
    if (!is_dir($this->coverDir)) {
      mkdir($this->coverDir, 0775, true);
    }

    $coverPath = $this->coverDir . $mbid . '.jpg';
    $coverContent = file_get_contents($coverUrl);
    file_put_contents($coverPath, $coverContent);

    return $mbid . '.jpg';
  }
}
