<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('relative_path', [$this, 'relativePath']),
        ];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return array(
            new TwigFilter('readable_filesize', [$this, 'readableFilesize']),
        );
    }


    public function relativePath(string $path): string
    {
        return ltrim($path, "/");
    }

    /**
     * @param integer $size
     * @param integer $precision
     * @param string  $space
     * @return string
     */
    public function readableFilesize($size, $precision = 2, $space = ' ')
    {
        if( $size <= 0 ) {
            return '0' . $space . 'KB';
        }

        if( $size === 1 ) {
            return '1' . $space . 'byte';
        }

        $mod = 1024;
        $units = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');

        for( $i = 0; $size > $mod && $i < count($units) - 1; ++$i ) {
            $size /= $mod;
        }

        return round($size, $precision) . $space . $units[$i];
    }

}