<?php

use Plank\Mediable\Commands\SyncMediaCommand;
use Plank\Mediable\Media;
use Illuminate\Contracts\Console\Kernel as Artisan;

class SyncMediaCommandTest extends TestCase
{
    public function test_it_calls_prune_and_install()
    {
        $command = $this->getMockBuilder(SyncMediaCommand::class)->setMethods(['call', 'option', 'argument'])->getMock();
        $command->expects($this->exactly(2))
            ->method('call')
            ->withConsecutive(
                [$this->equalTo('media:prune')],
                [$this->equalTo('media:import')]
            );
        $command->handle();
    }
}
