<?php

namespace Plank\Mediable\Tests\Integration;

use Plank\Mediable\ImageManipulation;
use Plank\Mediable\Tests\TestCase;

class ImageManipulationTest extends TestCase
{
    public function test_can_get_set_manipulation_callback()
    {
        $callback = $this->getCallback();
        $manipulation = new ImageManipulation($callback);
        $this->assertSame($callback, $manipulation->getCallback());
    }

    public function test_can_get_set_output_quality()
    {
        $manipulation = new ImageManipulation($this->getCallback());
        $this->assertEquals(90, $manipulation->getOutputQuality());
        $manipulation->setOutputQuality(-100);
        $this->assertEquals(0, $manipulation->getOutputQuality());
        $manipulation->setOutputQuality(500);
        $this->assertEquals(100, $manipulation->getOutputQuality());
        $manipulation->setOutputQuality(50);
        $this->assertEquals(50, $manipulation->getOutputQuality());
    }

    public function test_can_get_set_output_format()
    {
        $manipulation = new ImageManipulation($this->getCallback());
        $this->assertNull($manipulation->getOutputFormat());
        $manipulation->setOutputFormat('jpg');
        $this->assertEquals('jpg', $manipulation->getOutputFormat());
        $manipulation->toBmpFormat();
        $this->assertEquals('bmp', $manipulation->getOutputFormat());
        $manipulation->toGifFormat();
        $this->assertEquals('gif', $manipulation->getOutputFormat());
        $manipulation->toPngFormat();
        $this->assertEquals('png', $manipulation->getOutputFormat());
        $manipulation->toTiffFormat();
        $this->assertEquals('tif', $manipulation->getOutputFormat());
        $manipulation->toWebpFormat();
        $this->assertEquals('webp', $manipulation->getOutputFormat());
        $manipulation->toJpegFormat();
        $this->assertEquals('jpg', $manipulation->getOutputFormat());
    }

    public function test_can_get_set_before_save_callback()
    {
        $callback = $this->getCallback();
        $manipulation = new ImageManipulation($this->getCallback());

        $this->assertNull($manipulation->getBeforeSave());
        $manipulation->beforeSave($callback);
        $this->assertSame($callback, $manipulation->getBeforeSave());
    }

    private function getCallback()
    {
        return function () {
        };
    }
}
