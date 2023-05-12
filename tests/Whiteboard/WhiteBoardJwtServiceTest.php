<?php

namespace App\Tests\Whiteboard;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use App\Repository\RoomsRepository;
use App\Service\Whiteboard\WhiteboardJwtService;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WhiteBoardJwtServiceTest extends KernelTestCase
{
use RefreshDatabaseTrait;
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $whiteboardService = self::getContainer()->get(WhiteboardJwtService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(
            JWT::encode(
                [
                    'iat' => (new \DateTime())->getTimestamp(),
                    'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
                    'roles' => ['editor:' . $room->getUidReal()]
                ],
                'MY_SECRET'
            ),
            $whiteboardService->createJwt($room)
        );
        self::assertEquals(
            JWT::encode(
                [
                    'iat' => (new \DateTime())->getTimestamp(),
                    'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
                    'roles' => ['moderator:' . $room->getUidReal()]
                ],
                'MY_SECRET'
            ),
            $whiteboardService->createJwt($room, true)
        );
    }
}
