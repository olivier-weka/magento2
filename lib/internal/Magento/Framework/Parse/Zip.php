<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Various methods for parsing Zip codes
 *
 */
namespace Magento\Framework\Parse;

class Zip
{
    /**
     * Retrieve array of regions characterized by provided params
     *
     * @param string $state
     * @param string $zip
     * @return string[]
     */
    public static function parseRegions($state, $zip)
    {
        return !empty($zip) && $zip != '*' ? self::parseZip($zip) : ($state ? array($state) : array('*'));
    }

    /**
     * Retrieve array of regions characterized by provided zip code
     *
     * @param string $zip
     * @return string[]
     */
    public static function parseZip($zip)
    {
        if (strpos($zip, '-') == -1) {
            return array($zip);
        } else {
            return self::zipRangeToZipPattern($zip);
        }
    }

    /**
     * Convert a Magento zip range to an array of zip patterns
     * (e.g., 12000-13999 -> [12*, 13*])
     *
     * @param  string $zipRange
     * @return array
     */
    public static function zipRangeToZipPattern($zipRange)
    {
        $zipLength = 5;
        $zipPattern = array();

        if (!preg_match("/^(.+)-(.+)$/", $zipRange, $zipParts)) {
            return array($zipRange);
        }

        if ($zipParts[1] == $zipParts[2]) {
            return array($zipParts[1]);
        }

        if ($zipParts[1] > $zipParts[2]) {
            list($zipParts[2], $zipParts[1]) = array($zipParts[1], $zipParts[2]);
        }

        $from = str_split($zipParts[1]);
        $to = str_split($zipParts[2]);

        $startZip = '';
        $diffPosition = null;
        for ($pos = 0; $pos < $zipLength; $pos++) {
            if ($from[$pos] == $to[$pos]) {
                $startZip .= $from[$pos];
            } else {
                $diffPosition = $pos;
                break;
            }
        }

        /*
         * calculate zip-patterns
         */
        if (min(array_slice($to, $diffPosition)) == 9 && max(array_slice($from, $diffPosition)) == 0) {
            // particular case like 11000-11999 -> 11*
            return array($startZip . '*');
        } else {
            // calculate approximate zip-patterns
            $start = $from[$diffPosition];
            $finish = $to[$diffPosition];
            if ($diffPosition < $zipLength - 1) {
                $start++;
                $finish--;
            }
            $end = $diffPosition < $zipLength - 1 ? '*' : '';
            for ($digit = $start; $digit <= $finish; $digit++) {
                $zipPattern[] = $startZip . $digit . $end;
            }
        }

        if ($diffPosition == $zipLength - 1) {
            return $zipPattern;
        }

        $nextAsteriskFrom = true;
        $nextAsteriskTo = true;
        for ($pos = $zipLength - 1; $pos > $diffPosition; $pos--) {
            // calculate zip-patterns based on $from value
            if ($from[$pos] == 0 && $nextAsteriskFrom) {
                $nextAsteriskFrom = true;
            } else {
                $subZip = '';
                for ($k = $diffPosition; $k < $pos; $k++) {
                    $subZip .= $from[$k];
                }
                $delta = $nextAsteriskFrom ? 0 : 1;
                $end = $pos < $zipLength - 1 ? '*' : '';
                for ($i = $from[$pos] + $delta; $i <= 9; $i++) {
                    $zipPattern[] = $startZip . $subZip . $i . $end;
                }
                $nextAsteriskFrom = false;
            }

            // calculate zip-patterns based on $to value
            if ($to[$pos] == 9 && $nextAsteriskTo) {
                $nextAsteriskTo = true;
            } else {
                $subZip = '';
                for ($k = $diffPosition; $k < $pos; $k++) {
                    $subZip .= $to[$k];
                }
                $delta = $nextAsteriskTo ? 0 : 1;
                $end = $pos < $zipLength - 1 ? '*' : '';
                for ($i = 0; $i <= $to[$pos] - $delta; $i++) {
                    $zipPattern[] = $startZip . $subZip . $i . $end;
                }
                $nextAsteriskTo = false;
            }
        }

        if ($nextAsteriskFrom) {
            $zipPattern[] = $startZip . $from[$diffPosition] . '*';
        }
        if ($nextAsteriskTo) {
            $zipPattern[] = $startZip . $to[$diffPosition] . '*';
        }

        return $zipPattern;
    }
}
