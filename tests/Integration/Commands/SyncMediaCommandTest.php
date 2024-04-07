<?php

namespace Plank\Mediable\Tests\Integration\Commands;

use PHPUnit\Framework\MockObject\MockObject;
use Plank\Mediable\Commands\SyncMediaCommand;
use Plank\Mediable\Tests\TestCase;

class SyncMediaCommandTest extends TestCase
{
    public function test_it_calls_prune_and_install(): void
    {
        $this->withoutMockingConsoleOutput();
        /** @var SyncMediaCommand|MockObject $command */
        $command = $this->getMockBuilder(SyncMediaCommand::class)
            ->onlyMethods(['call', 'option', 'argument'])
            ->getMock();
        $command->expects($this->exactly(2))
            ->method('call')
            ->with(...$this->withConsecutive(
                [
                    $this->equalTo('media:prune'),
                    [
                        'disk' => null,
                        '--directory' => '',
                        '--non-recursive' => false,
                    ]
                ],
                [
                    $this->equalTo('media:import'),
                    [
                        'disk' => null,
                        '--directory' => '',
                        '--non-recursive' => false,
                        '--force' => false
                    ]
                ]
            ));

        $command->handle();
    }
}
