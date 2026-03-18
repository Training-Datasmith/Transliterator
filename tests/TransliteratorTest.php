<?php

declare(strict_types=1);

namespace Behat\Tests\Transliterator;

use Behat\Transliterator\Transliterator;
use PHPUnit\Framework\TestCase;

class TransliteratorTest extends TestCase
{
    /**
     * @dataProvider provideUtf8ConversionCases
     */
    public function testUtf8Conversion($input, $expected)
    {
        $this->assertSame($expected, Transliterator::utf8ToAscii($input));
    }

    public function provideUtf8ConversionCases()
    {
        return [
            ['', ''],
            ['BonJour', 'BonJour'],
            ['Déjà', 'Deja'],
            ['trąnslįteration tėst ųsąge ūž', 'transliteration test usage uz'],
            ['това е тестово заглавие', 'tova e testovo zaglavie'],
            ['це є тестовий заголовок з ґ, є, ї, і', 'tse ie testovii zagolovok z g\', ie, yi, i'],
            ['это тестовый заголовок', 'eto testovyi zagolovok'],
            ['führen Aktivitäten Haglöfs', 'fuhren Aktivitaten Haglofs'],
        ];
    }

    /**
     * @dataProvider provideTransliterationCases
     */
    public function testTransliteration($input, $expected)
    {
        $this->assertSame($expected, Transliterator::transliterate($input));
    }

    public function provideTransliterationCases()
    {
        return [
            ['', ''],
            ['BonJour', 'bonjour'],
            ['BonJour & au revoir', 'bonjour-au-revoir'],
            ['Déjà', 'deja'],
            ['trąnslįteration tėst ųsąge ūž', 'transliteration-test-usage-uz'],
            ['това е тестово заглавие', 'tova-e-testovo-zaglavie'],
            ['це є тестовий заголовок з ґ, є, ї, і', 'tse-ie-testovii-zagolovok-z-g-ie-yi-i'],
            ['это тестовый заголовок', 'eto-testovyi-zagolovok'],
            ['führen Aktivitäten Haglöfs', 'fuhren-aktivitaten-haglofs'],
            ["that it's 'eleven' 'o'clock'", 'that-its-eleven-oclock'],
        ];
    }

    /**
     * @dataProvider provideUnaccentCases
     */
    public function testUnaccent($input, $expected)
    {
        $this->assertSame($expected, Transliterator::unaccent($input));
    }

    public function provideUnaccentCases()
    {
        return [
            ['', ''],
            ['BonJour', 'BonJour'],
            ['Déjà', 'Deja'],
            ['това е тестово заглавие', 'това е тестово заглавие'],
            ['це є тестовий заголовок з ґ, є, ї, і', 'це є тестовий заголовок з ґ, є, ї, і'],
        ];
    }

    /**
     * @dataProvider provideUrlizationCases
     */
    public function testUrlization($input, $expected)
    {
        $this->assertSame($expected, Transliterator::urlize($input));
    }

    public function provideUrlizationCases()
    {
        return [
            ['', ''],
            ['BonJour', 'bonjour'],
            ['BonJour & au revoir', 'bonjour-au-revoir'],
            ['Déjà', 'deja'],
            ['това е тестово заглавие', ''],
            ['це є тестовий заголовок з ґ, є, ї, і', ''],
        ];
    }
}
