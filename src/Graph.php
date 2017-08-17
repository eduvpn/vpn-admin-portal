<?php

/**
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2017, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Admin;

use DateInterval;
use DateTime;

class Graph
{
    /** @var \DateTime */
    private $dateTime;

    /** @var string */
    private $fontFile = '/usr/share/fonts/google-roboto/Roboto-Regular.ttf';
    //private $fontFile = '/usr/share/fonts/roboto_fontface/Roboto-Regular.ttf';

    /** @var int */
    private $fontSize = 10;

    /** @var array */
    private $imageSize = [600, 300];

    /**
     * @param \DateTime $dateTime
     */
    public function __construct(DateTime $dateTime = null)
    {
        if (is_null($dateTime)) {
            $dateTime = new DateTime();
        }
        $this->dateTime = $dateTime;
    }

    /**
     * @param string $fontFile
     */
    public function setFontFile($fontFile)
    {
        $this->fontFile = $fontFile;
    }

    /**
     * @param array    $graphData where the key is the date of the format `Y-m-d`
     *                            and the value is the value to plot
     * @param callable $toHuman   a function to convert the values to human
     *                            readable form
     *
     * @return string the PNG logo data
     */
    public function draw(array $graphData, callable $toHuman = null, DateInterval $dateInterval = null)
    {
        if (is_null($dateInterval)) {
            $dateInterval = new DateInterval('P1M');
        }

        if (is_null($toHuman)) {
            $toHuman = function ($v) {
                return sprintf('%d ', $v);
            };
        }

        $dateList = $this->createDateList($dateInterval);

        // merge data
        foreach ($dateList as $k => $v) {
            if (array_key_exists($k, $graphData)) {
                $dateList[$k] = $graphData[$k];
            }
        }

        $maxValue = $this->getMaxValue($dateList);
        $yAxisTopText = $toHuman($maxValue);
        //var_dump($yAxisTopText);
        $yAxisMiddleText = $toHuman($maxValue / 2);
        //var_dump($yAxisMiddleText);
        $yAxisTextWidth = max($this->textWidth($yAxisTopText), $this->textWidth($yAxisMiddleText));
        //var_dump($yAxisTextWidth);
        $yAxisTextHeight = max($this->textHeight($yAxisTopText), $this->textHeight($yAxisMiddleText));
        //var_dump($yAxisTextHeight);
        $relativeDateList = $this->toRelativeValues($dateList);

        // XXX loop over all text fields and determine MAX
        $xAxisTextHeight = $this->textHeight('2017-01-01');
        $xAxisTextWidth = $this->textWidth('2017-01-01') + 4;

        $xOffset = $yAxisTextWidth;
        $yOffset = $yAxisTextHeight / 2;

        // drawing lines etc is done with x,y starting at lower left bottom
        // drawing text is done with x,y starting at top left
        $img = imagecreatetruecolor($this->imageSize[0], $this->imageSize[1]);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));

        $textColor = imagecolorallocate($img, 0x55, 0x55, 0x55);
        $barColor = imagecolorallocate($img, 0xdf, 0x7f, 0x0c);
        $lineColor = imagecolorallocate($img, 0xdd, 0xdd, 0xdd);

        // array imagettftext ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )
        // topText
        imagettftext(
            $img,
            $this->fontSize,
            0, // angle
            0, // x
            $yAxisTextHeight, // y
            $textColor,
            $this->fontFile,
            $yAxisTopText
        );

        $xAxisTotalBarSpace = $this->imageSize[0] - $xOffset;
        $numberOfBars = count($relativeDateList);
        $xAxisSpacePerBar = $xAxisTotalBarSpace / $numberOfBars;
        $yAxisTotalBarSpace = $this->imageSize[1] - $yOffset - $xAxisTextWidth;

        // middleText
        imagettftext(
            $img,
            $this->fontSize,
            0, // angle
            0, // x
            $yAxisTextHeight + $yAxisTotalBarSpace / 2, // y
            $textColor,
            $this->fontFile,
            $yAxisMiddleText
        );

        // draw top line
        imageline(
            $img,
            $xOffset,
            $yAxisTextHeight / 2,
            $this->imageSize[0],
            $yAxisTextHeight / 2,
            $lineColor
        );

        // draw half line
        imageline(
            $img,
            $xOffset,
            $yAxisTextHeight / 2 + $yAxisTotalBarSpace / 2,
            $this->imageSize[0],
            $yAxisTextHeight / 2 + $yAxisTotalBarSpace / 2,
            $lineColor
        );

        $dateList = array_keys($relativeDateList);
        $valueList = array_values($relativeDateList);

        for ($i = 0; $i < $numberOfBars; ++$i) {
            $yPixels = $valueList[$i] * $yAxisTotalBarSpace;
            $this->drawBar(
                $img,
                $xOffset + $i * $xAxisSpacePerBar,
                $xAxisTextWidth,
                $xOffset + ($i + 1) * $xAxisSpacePerBar,
                $xAxisTextWidth + $yPixels,
                $barColor
            );

            // write xAxis dates
            if (0 === $i % 3) {
                imagettftext(
                    $img,
                    $this->fontSize,
                    90, // angle
                    $xOffset + $i * $xAxisSpacePerBar + $xAxisSpacePerBar / 2 + $xAxisTextHeight / 2,
                    $this->imageSize[1],
                    $textColor,
                    $this->fontFile,
                    $dateList[$i]
                );
            }
        }

        // buffer image output and return it as a value
        ob_start();
        imagepng($img);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);

        return $imageData;
    }

    /**
     * Create a list of dates from $dateInterval ago until now.
     *
     * @param \DateInterval $dateInterval
     *
     * @return array
     */
    private function createDateList(DateInterval $dateInterval)
    {
        $currentDay = $this->dateTime->format('Y-m-d');
        $dateTime = clone $this->dateTime;
        $dateTime->sub($dateInterval);
        $oneDay = new DateInterval('P1D');

        $dateList = [];
        while ($dateTime < $this->dateTime) {
            $dateList[$dateTime->format('Y-m-d')] = 0;
            $dateTime->add($oneDay);
        }

        return $dateList;
    }

    /**
     * Get the maximum value in the dataset.
     *
     * @param array $dateList
     */
    private function getMaxValue(array $dateList)
    {
        $maxValue = 0;
        foreach ($dateList as $k => $v) {
            if ($v > $maxValue) {
                $maxValue = $v;
            }
        }

        return $maxValue;
    }

    /**
     * Convert the absolute values of the data to relative values, where the
     * highest value is converted to 1.
     *
     * @param array $dateList
     *
     * @return array
     */
    private function toRelativeValues(array $dateList)
    {
        $maxValue = $this->getMaxValue($dateList);
        if (0 !== $maxValue) {
            foreach ($dateList as $k => $v) {
                $dateList[$k] = $v / $maxValue;
            }
        }

        return $dateList;
    }

    /**
     * Determine the width of the box containing the text with the selected
     * font and size.
     *
     * @param string $textString
     *
     * @return int
     */
    private function textWidth($textString)
    {
        if (false === $textBox = imagettfbbox($this->fontSize, 0, $this->fontFile, $textString)) {
            throw new GraphException('unable to determine width of text in box');
        }

        return $textBox[4];
    }

    /**
     * Determine the height of the box containing the text with the selected
     * font and size.
     *
     * @param string $textString
     *
     * @return int
     */
    private function textHeight($textString)
    {
        if (false === $textBox = imagettfbbox($this->fontSize, 0, $this->fontFile, $textString)) {
            throw new GraphException('unable to determine height of text in box');
        }

        return -$textBox[5];
    }

    private function drawBar($img, $x1, $y1, $x2, $y2, $color)
    {
        // (0,0) is top left instead of bottom left
        imagefilledrectangle(
            $img,
            $x1 + 2,
            $this->imageSize[1] - $y1,
            $x2 - 2,
            $this->imageSize[1] - $y2,
            $color
        );
    }
}
