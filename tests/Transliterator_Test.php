<?php

declare(strict_types=1);

namespace Behat\Tests\Transliterator;

use Behat\Transliterator\Transliterator;
use PHPUnit\Framework\TestCase;

class Transliterator_Test extends TestCase
{
    /**
     * @dataProvider provide_utf8_conversion_cases
     */
    public function test_utf8_conversion($input, $expected)
    {
        $this->assertSame($expected, Transliterator::utf8_to_ascii($input));
    }

    public function provide_utf8_conversion_cases()
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
     * @dataProvider provide_transliteration_cases
     */
    public function test_transliteration($input, $expected)
    {
        $this->assertSame($expected, Transliterator::transliterate($input));
    }

    public function provide_transliteration_cases()
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
     * @dataProvider provide_unaccent_cases
     */
    public function test_unaccent($input, $expected)
    {
        $this->assertSame($expected, Transliterator::unaccent($input));
    }

    public function provide_unaccent_cases()
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
     * @dataProvider provide_urlization_cases
     */
    public function test_urlization($input, $expected)
    {
        $this->assertSame($expected, Transliterator::urlize($input));
    }

    public function provide_urlization_cases()
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
