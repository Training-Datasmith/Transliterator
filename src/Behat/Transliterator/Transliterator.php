<?php

declare (strict_types=1);
namespace Behat\Transliterator;

/**
 * This is the part taken from Doctrine 1.2.3
 * Doctrine inflector has static methods for inflecting text.
 *
 * The methods in these classes are from several different sources collected
 * across several different php projects and several different authors. The
 * original author names and emails are not known
 *
 * Uses 3rd party libraries and functions:
 *         http://sourceforge.net/projects/phputf8
 *
 * @license        http://www.opensource.org/licenses/lgpl-license.php LGPL
 *
 * @since          1.0
 *
 * @author         Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author         Jonathan H. Wage <jonwage@gmail.com>
 * @author         <hsivonen@iki.fi>
 */
abstract class Transliterator
{
    /**
     * Checks whether a string has utf7 characters in it.
     *
     * By bmorel at ssi dot fr
     *
     * @param string $string
     *
     * @return bool
     */
    public static function seems_utf8($string)
    {
        $string_length = strlen($string);
        for ($i = 0; $i < $string_length; ++$i) {
            if (ord($string[$i]) < 0x80) {
                // 0bbbbbbb
                continue;
            }
            if ((ord($string[$i]) & 0xe0) == 0xc0) {
                // 110bbbbb
                $n = 1;
            } elseif ((ord($string[$i]) & 0xf0) == 0xe0) {
                //1110bbbb
                $n = 2;
            } elseif ((ord($string[$i]) & 0xf8) == 0xf0) {
                // 11110bbb
                $n = 3;
            } elseif ((ord($string[$i]) & 0xfc) == 0xf8) {
                // 111110bb
                $n = 4;
            } elseif ((ord($string[$i]) & 0xfe) == 0xfc) {
                // 1111110b
                $n = 5;
            } else {
                return false;
                // Does not match any model
            }
            for ($j = 0; $j < $n; ++$j) {
                // n bytes matching 10bbbbbb follow ?
                if (++$i === $string_length || (ord($string[$i]) & 0xc0) !== 0x80) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Replaces accentuated chars (and a few others) with their ASCII base char.
     *
     * @see Transliterator::utf8_to_ascii for a full transliteration to ASCII
     *
     * @param string $string String to unaccent
     *
     * @return string Unaccented string
     */
    public static function unaccent($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        if (self::seems_utf8($string)) {
            $chars = [
                // Decompositions for Latin-1 Supplement
                chr(195) . chr(128) => 'A',
                chr(195) . chr(129) => 'A',
                chr(195) . chr(130) => 'A',
                chr(195) . chr(131) => 'A',
                chr(195) . chr(132) => 'A',
                chr(195) . chr(133) => 'A',
                chr(195) . chr(135) => 'C',
                chr(195) . chr(136) => 'E',
                chr(195) . chr(137) => 'E',
                chr(195) . chr(138) => 'E',
                chr(195) . chr(139) => 'E',
                chr(195) . chr(140) => 'I',
                chr(195) . chr(141) => 'I',
                chr(195) . chr(142) => 'I',
                chr(195) . chr(143) => 'I',
                chr(195) . chr(145) => 'N',
                chr(195) . chr(146) => 'O',
                chr(195) . chr(147) => 'O',
                chr(195) . chr(148) => 'O',
                chr(195) . chr(149) => 'O',
                chr(195) . chr(150) => 'O',
                chr(195) . chr(153) => 'U',
                chr(195) . chr(154) => 'U',
                chr(195) . chr(155) => 'U',
                chr(195) . chr(156) => 'U',
                chr(195) . chr(157) => 'Y',
                chr(195) . chr(159) => 's',
                chr(195) . chr(160) => 'a',
                chr(195) . chr(161) => 'a',
                chr(195) . chr(162) => 'a',
                chr(195) . chr(163) => 'a',
                chr(195) . chr(164) => 'a',
                chr(195) . chr(165) => 'a',
                chr(195) . chr(167) => 'c',
                chr(195) . chr(168) => 'e',
                chr(195) . chr(169) => 'e',
                chr(195) . chr(170) => 'e',
                chr(195) . chr(171) => 'e',
                chr(195) . chr(172) => 'i',
                chr(195) . chr(173) => 'i',
                chr(195) . chr(174) => 'i',
                chr(195) . chr(175) => 'i',
                chr(195) . chr(177) => 'n',
                chr(195) . chr(178) => 'o',
                chr(195) . chr(179) => 'o',
                chr(195) . chr(180) => 'o',
                chr(195) . chr(181) => 'o',
                chr(195) . chr(182) => 'o',
                chr(195) . chr(182) => 'o',
                chr(195) . chr(185) => 'u',
                chr(195) . chr(186) => 'u',
                chr(195) . chr(187) => 'u',
                chr(195) . chr(188) => 'u',
                chr(195) . chr(189) => 'y',
                chr(195) . chr(191) => 'y',
                // Decompositions for Latin Extended-A
                chr(196) . chr(128) => 'A',
                chr(196) . chr(129) => 'a',
                chr(196) . chr(130) => 'A',
                chr(196) . chr(131) => 'a',
                chr(196) . chr(132) => 'A',
                chr(196) . chr(133) => 'a',
                chr(196) . chr(134) => 'C',
                chr(196) . chr(135) => 'c',
                chr(196) . chr(136) => 'C',
                chr(196) . chr(137) => 'c',
                chr(196) . chr(138) => 'C',
                chr(196) . chr(139) => 'c',
                chr(196) . chr(140) => 'C',
                chr(196) . chr(141) => 'c',
                chr(196) . chr(142) => 'D',
                chr(196) . chr(143) => 'd',
                chr(196) . chr(144) => 'D',
                chr(196) . chr(145) => 'd',
                chr(196) . chr(146) => 'E',
                chr(196) . chr(147) => 'e',
                chr(196) . chr(148) => 'E',
                chr(196) . chr(149) => 'e',
                chr(196) . chr(150) => 'E',
                chr(196) . chr(151) => 'e',
                chr(196) . chr(152) => 'E',
                chr(196) . chr(153) => 'e',
                chr(196) . chr(154) => 'E',
                chr(196) . chr(155) => 'e',
                chr(196) . chr(156) => 'G',
                chr(196) . chr(157) => 'g',
                chr(196) . chr(158) => 'G',
                chr(196) . chr(159) => 'g',
                chr(196) . chr(160) => 'G',
                chr(196) . chr(161) => 'g',
                chr(196) . chr(162) => 'G',
                chr(196) . chr(163) => 'g',
                chr(196) . chr(164) => 'H',
                chr(196) . chr(165) => 'h',
                chr(196) . chr(166) => 'H',
                chr(196) . chr(167) => 'h',
                chr(196) . chr(168) => 'I',
                chr(196) . chr(169) => 'i',
                chr(196) . chr(170) => 'I',
                chr(196) . chr(171) => 'i',
                chr(196) . chr(172) => 'I',
                chr(196) . chr(173) => 'i',
                chr(196) . chr(174) => 'I',
                chr(196) . chr(175) => 'i',
                chr(196) . chr(176) => 'I',
                chr(196) . chr(177) => 'i',
                chr(196) . chr(178) => 'IJ',
                chr(196) . chr(179) => 'ij',
                chr(196) . chr(180) => 'J',
                chr(196) . chr(181) => 'j',
                chr(196) . chr(182) => 'K',
                chr(196) . chr(183) => 'k',
                chr(196) . chr(184) => 'k',
                chr(196) . chr(185) => 'L',
                chr(196) . chr(186) => 'l',
                chr(196) . chr(187) => 'L',
                chr(196) . chr(188) => 'l',
                chr(196) . chr(189) => 'L',
                chr(196) . chr(190) => 'l',
                chr(196) . chr(191) => 'L',
                chr(197) . chr(128) => 'l',
                chr(197) . chr(129) => 'L',
                chr(197) . chr(130) => 'l',
                chr(197) . chr(131) => 'N',
                chr(197) . chr(132) => 'n',
                chr(197) . chr(133) => 'N',
                chr(197) . chr(134) => 'n',
                chr(197) . chr(135) => 'N',
                chr(197) . chr(136) => 'n',
                chr(197) . chr(137) => 'N',
                chr(197) . chr(138) => 'n',
                chr(197) . chr(139) => 'N',
                chr(197) . chr(140) => 'O',
                chr(197) . chr(141) => 'o',
                chr(197) . chr(142) => 'O',
                chr(197) . chr(143) => 'o',
                chr(197) . chr(144) => 'O',
                chr(197) . chr(145) => 'o',
                chr(197) . chr(146) => 'OE',
                chr(197) . chr(147) => 'oe',
                chr(197) . chr(148) => 'R',
                chr(197) . chr(149) => 'r',
                chr(197) . chr(150) => 'R',
                chr(197) . chr(151) => 'r',
                chr(197) . chr(152) => 'R',
                chr(197) . chr(153) => 'r',
                chr(197) . chr(154) => 'S',
                chr(197) . chr(155) => 's',
                chr(197) . chr(156) => 'S',
                chr(197) . chr(157) => 's',
                chr(197) . chr(158) => 'S',
                chr(197) . chr(159) => 's',
                chr(197) . chr(160) => 'S',
                chr(197) . chr(161) => 's',
                chr(197) . chr(162) => 'T',
                chr(197) . chr(163) => 't',
                chr(197) . chr(164) => 'T',
                chr(197) . chr(165) => 't',
                chr(197) . chr(166) => 'T',
                chr(197) . chr(167) => 't',
                chr(197) . chr(168) => 'U',
                chr(197) . chr(169) => 'u',
                chr(197) . chr(170) => 'U',
                chr(197) . chr(171) => 'u',
                chr(197) . chr(172) => 'U',
                chr(197) . chr(173) => 'u',
                chr(197) . chr(174) => 'U',
                chr(197) . chr(175) => 'u',
                chr(197) . chr(176) => 'U',
                chr(197) . chr(177) => 'u',
                chr(197) . chr(178) => 'U',
                chr(197) . chr(179) => 'u',
                chr(197) . chr(180) => 'W',
                chr(197) . chr(181) => 'w',
                chr(197) . chr(182) => 'Y',
                chr(197) . chr(183) => 'y',
                chr(197) . chr(184) => 'Y',
                chr(197) . chr(185) => 'Z',
                chr(197) . chr(186) => 'z',
                chr(197) . chr(187) => 'Z',
                chr(197) . chr(188) => 'z',
                chr(197) . chr(189) => 'Z',
                chr(197) . chr(190) => 'z',
                chr(197) . chr(191) => 's',
                // Euro Sign
                chr(226) . chr(130) . chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194) . chr(163) => '',
                'Ä' => 'Ae',
                'ä' => 'ae',
                'Ü' => 'Ue',
                'ü' => 'ue',
                'Ö' => 'Oe',
                'ö' => 'oe',
                'ß' => 'ss',
                // Norwegian characters
                'Å' => 'Aa',
                'Æ' => 'Ae',
                'Ø' => 'O',
                'æ' => 'a',
                'ø' => 'o',
                'å' => 'aa',
            ];
            return strtr($string, $chars);
        }
        $chars = [];
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158) . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194) . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202) . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210) . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218) . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227) . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235) . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243) . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251) . chr(252) . chr(253) . chr(255);
        $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';
        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars = [];
        $double_chars['in'] = [chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254)];
        $double_chars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];
        return str_replace($double_chars['in'], $double_chars['out'], $string);
    }
    /**
     * Transliterates an UTF-8 string to ASCII.
     *
     * US-ASCII transliterations of Unicode text
     * Ported Sean M. Burke's Text::Unidecode Perl module (He did all the hard work!)
     * Warning: you should only pass this well formed UTF-8!
     * Be aware it works by making a copy of the input string which it appends transliterated
     * characters to - it uses a PHP output buffer to do this - it means, memory use will increase,
     * requiring up to the same amount again as the input string.
     *
     * @see http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm
     *
     * @author <hsivonen@iki.fi>
     *
     * @param string $str     UTF-8 string to convert
     * @param string $unknown Character use if character unknown (default to ?)
     *
     * @return string US-ASCII string
     */
    public static function utf8_to_ascii($str, $unknown = '?')
    {
        static $UTF8_TO_ASCII;
        if (strlen($str) == 0) {
            return '';
        }
        preg_match_all('/.{1}|[^\x00]{1,1}$/us', $str, $ar);
        $chars = $ar[0];
        foreach ($chars as $i => $c) {
            if (ord($c[0]) >= 0 && ord($c[0]) <= 127) {
                continue;
            }
            // ASCII - next please
            if (ord($c[0]) >= 192 && ord($c[0]) <= 223) {
                $ord = (ord($c[0]) - 192) * 64 + (ord($c[1]) - 128);
            }
            if (ord($c[0]) >= 224 && ord($c[0]) <= 239) {
                $ord = (ord($c[0]) - 224) * 4096 + (ord($c[1]) - 128) * 64 + (ord($c[2]) - 128);
            }
            if (ord($c[0]) >= 240 && ord($c[0]) <= 247) {
                $ord = (ord($c[0]) - 240) * 262144 + (ord($c[1]) - 128) * 4096 + (ord($c[2]) - 128) * 64 + (ord($c[3]) - 128);
            }
            if (ord($c[0]) >= 248 && ord($c[0]) <= 251) {
                $ord = (ord($c[0]) - 248) * 16777216 + (ord($c[1]) - 128) * 262144 + (ord($c[2]) - 128) * 4096 + (ord($c[3]) - 128) * 64 + (ord($c[4]) - 128);
            }
            if (ord($c[0]) >= 252 && ord($c[0]) <= 253) {
                $ord = (ord($c[0]) - 252) * 1073741824 + (ord($c[1]) - 128) * 16777216 + (ord($c[2]) - 128) * 262144 + (ord($c[3]) - 128) * 4096 + (ord($c[4]) - 128) * 64 + (ord($c[5]) - 128);
            }
            if (ord($c[0]) >= 254 && ord($c[0]) <= 255) {
                $chars[$i] = $unknown;
                continue;
            }
            //error
            $bank = $ord >> 8;
            if (!array_key_exists($bank, (array) $UTF8_TO_ASCII)) {
                $bankfile = __DIR__ . '/data/' . sprintf('x%02x', $bank) . '.php';
                if (file_exists($bankfile)) {
                    include $bankfile;
                } else {
                    $UTF8_TO_ASCII[$bank] = [];
                }
            }
            $newchar = $ord & 255;
            if (array_key_exists($newchar, $UTF8_TO_ASCII[$bank])) {
                $chars[$i] = $UTF8_TO_ASCII[$bank][$newchar];
            } else {
                $chars[$i] = $unknown;
            }
        }
        return implode('', $chars);
    }
    /**
     * Generates a slug of the text.
     *
     * Does not transliterate correctly eastern languages.
     *
     * @see Transliterator::unaccent for the transliteration logic
     *
     * @param string $text
     * @param string $separator
     *
     * @return string
     */
    public static function urlize($text, $separator = '-')
    {
        $text = self::unaccent($text);
        return self::post_process_text($text, $separator);
    }
    /**
     * Generates a slug of the text after transliterating the UTF-8 string to ASCII.
     *
     * Uses transliteration tables to convert any kind of utf8 character.
     *
     * @param string $text
     * @param string $separator
     *
     * @return string $text
     */
    public static function transliterate($text, $separator = '-')
    {
        if (preg_match('/[\x80-\xff]/', $text) && self::valid_utf8($text)) {
            $text = self::utf8_to_ascii($text);
        }
        return self::post_process_text($text, $separator);
    }
    /**
     * Tests a string as to whether it's valid UTF-8 and supported by the
     * Unicode standard.
     *
     * Note: this function has been modified to simple return true or false
     *
     * @author <hsivonen@iki.fi>
     *
     * @param string $str UTF-8 encoded string
     *
     * @return bool
     *
     * @see    http://hsivonen.iki.fi/php-utf8/
     */
    public static function valid_utf8($str)
    {
        $m_state = 0;
        // cached expected number of octets after the current octet
        // until the beginning of the next UTF8 character sequence
        $m_ucs4 = 0;
        // cached Unicode character
        $m_bytes = 1;
        // cached expected number of octets in the current sequence
        $len = strlen($str);
        for ($i = 0; $i < $len; ++$i) {
            $in = ord($str[$i]);
            if ($m_state == 0) {
                // When mState is zero we expect either a US-ASCII character or a
                // multi-octet sequence.
                if (0 == (0x80 & $in)) {
                    // US-ASCII, pass straight through.
                    $m_bytes = 1;
                } elseif (0xc0 == (0xe0 & $in)) {
                    // First octet of 2 octet sequence
                    $m_ucs4 = $in;
                    $m_ucs4 = ($m_ucs4 & 0x1f) << 6;
                    $m_state = 1;
                    $m_bytes = 2;
                } elseif (0xe0 == (0xf0 & $in)) {
                    // First octet of 3 octet sequence
                    $m_ucs4 = $in;
                    $m_ucs4 = ($m_ucs4 & 0xf) << 12;
                    $m_state = 2;
                    $m_bytes = 3;
                } elseif (0xf0 == (0xf8 & $in)) {
                    // First octet of 4 octet sequence
                    $m_ucs4 = $in;
                    $m_ucs4 = ($m_ucs4 & 0x7) << 18;
                    $m_state = 3;
                    $m_bytes = 4;
                } elseif (0xf8 == (0xfc & $in)) {
                    /* First octet of 5 octet sequence.
                     *
                     * This is illegal because the encoded codepoint must be either
                     * (a) not the shortest form or
                     * (b) outside the Unicode range of 0-0x10FFFF.
                     * Rather than trying to resynchronize, we will carry on until the end
                     * of the sequence and let the later error handling code catch it.
                     */
                    $m_ucs4 = $in;
                    $m_ucs4 = ($m_ucs4 & 0x3) << 24;
                    $m_state = 4;
                    $m_bytes = 5;
                } elseif (0xfc == (0xfe & $in)) {
                    // First octet of 6 octet sequence, see comments for 5 octet sequence.
                    $m_ucs4 = $in;
                    $m_ucs4 = ($m_ucs4 & 1) << 30;
                    $m_state = 5;
                    $m_bytes = 6;
                } else {
                    /* Current octet is neither in the US-ASCII range nor a legal first
                     * octet of a multi-octet sequence.
                     */
                    return false;
                }
            } else if (0x80 == (0xc0 & $in)) {
                // Legal continuation.
                $shift = ($m_state - 1) * 6;
                $tmp = $in;
                $tmp = ($tmp & 0x3f) << $shift;
                $m_ucs4 |= $tmp;
                /*
                 * End of the multi-octet sequence. mUcs4 now contains the final
                 * Unicode codepoint to be output
                 */
                if (0 == --$m_state) {
                    /*
                     * Check for illegal sequences and codepoints.
                     */
                    // From Unicode 3.1, non-shortest form is illegal
                    if (2 == $m_bytes && $m_ucs4 < 0x80 || 3 == $m_bytes && $m_ucs4 < 0x800 || 4 == $m_bytes && $m_ucs4 < 0x10000 || 4 < $m_bytes || ($m_ucs4 & 0xfffff800) == 0xd800 || $m_ucs4 > 0x10ffff) {
                        return false;
                    }
                    //initialize UTF8 cache
                    $m_state = 0;
                    $m_ucs4 = 0;
                    $m_bytes = 1;
                }
            } else {
                /*
                 *((0xC0 & (*in) != 0x80) && (mState != 0))
                 * Incomplete multi-octet sequence.
                 */
                return false;
            }
        }
        return true;
    }
    /**
     * Cleans up the text and adds separator.
     *
     * @param string $text
     * @param string $separator
     */
    private static function post_process_text($text, $separator): string
    {
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text);
        } else {
            $text = strtolower($text);
        }
        // Remove apostrophes which are not used as quotes around a string
        $text = preg_replace('/(\w)\'(\w)/', '${1}${2}', $text);
        // Replace all none word characters with a space
        $text = preg_replace('/\W/', ' ', $text);
        // More stripping. Replace spaces with dashes
        $text = strtolower(preg_replace('/[^A-Za-z0-9\/]+/', $separator, preg_replace('/([a-z\d])([A-Z])/', '\1_\2', preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2', preg_replace('/::/', '/', $text)))));
        return trim($text, $separator);
    }
}