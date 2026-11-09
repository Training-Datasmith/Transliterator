<?php

declare(strict_types=1);

namespace Behat\Tests\Transliterator;

use PHPUnit\Framework\TestCase;

class Data_Test extends TestCase
{
    /**
     * @dataProvider provide_data_files
     */
    public function test_data_file_integrity($file)
    {
        $UTF8_TO_ASCII = [];

        require __DIR__.'/../src/Behat/Transliterator/data/'.$file;

        $this->assertCount(1, $UTF8_TO_ASCII, 'Each data file should register a single key in $UTF8_TO_ASCII.');

        $data = current($UTF8_TO_ASCII);

        $this->assertIsArray($data, 'The value in $UTF8_TO_ASCII should be an array.');

        $this->assertEquals(256, count($data), 'The value in $UTF8_TO_ASCII should have 256 elements.');
    }

    public function provide_data_files()
    {
        $files = [];

        $iterator = new \FilesystemIterator(__DIR__.'/../src/Behat/Transliterator/data');

        foreach ($iterator as $file) {
            $files[] = [$file->getFilename()];
        }

        return $files;
    }
}
