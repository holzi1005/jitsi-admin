<?php

namespace App\Tests\callOut;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Callout\CalloutSessionAPIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalloutApiPoolTest extends KernelTestCase
{
use RefreshDatabaseTrait;
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);

        $calloutSession1 = new CalloutSession();
        $calloutSession1->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($room->getModerator())
            ->setState(0)
            ->setUid('ksdlfjlkfds')
            ->setLeftRetries(2);
        $manager->persist($calloutSession1);
        $calloutSession2 = new CalloutSession();
        $calloutSession2->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($room->getModerator())
            ->setState(10)
            ->setUid('ksdlfjlkfdfgsdds')
            ->setLeftRetries(2);
        $manager->persist($calloutSession2);
        $calloutSession3 = new CalloutSession();
        $calloutSession3->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($room->getModerator())
            ->setState(20)
            ->setUid('ksddfglfjlkfds')
            ->setLeftRetries(2);
        $manager->persist($calloutSession3);
        $callerUserId = new CallerId();
        $callerUserId->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setUser($user)
            ->setCallerId('987654321');
        $manager->persist($callerUserId);
        $manager->flush();
    }

    public function testgetCalloutSessionByState(): void
    {
        $kernel = self::bootKernel();
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        self::assertEquals(1, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$INITIATED)));
        self::assertEquals(1, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$DIALED)));
        self::assertEquals(1, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$ON_HOLD)));
        self::assertEquals(0, sizeof($calloutSessionAPIService->findCalloutSessionByState(4)));
    }

    public function testBuildCalloutSessionPoolArray(): void
    {
        $kernel = self::bootKernel();
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        $calloutSession = $calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$INITIATED)[0];
        $calloutArr = $calloutSessionAPIService->buildCallerSessionPoolArray($calloutSession);
        self::assertEquals(
            [
                'state' => 'INITIATED',
                'call_number' => '987654321012',
                'sip_room_number' => '12340',
                'sip_pin' => '987654321',
                'display_name' => 'Sie wurden von Test1, 1234, User, Test eingeladen',
                'tag' => null,
                'organisator' => 'Test1, 1234, User, Test',
                'title' => 'TestMeeting: 0',
                'links' => ['dial' => '/api/v1/call/out/dial/ksdlfjlkfds']
            ],
            $calloutArr
        );
    }

    public function testBuildCalloutSessionPoolArrayNull(): void
    {
        $kernel = self::bootKernel();
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        $calloutSession = $calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$INITIATED)[0];
        foreach ($calloutSession->getRoom()->getCallerIds() as $data) {
            $calloutSession->getRoom()->removeCallerId($data);
        }
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($calloutSession);
        $manager->flush();

        $calloutArr = $calloutSessionAPIService->buildCallerSessionPoolArray($calloutSession);
        self::assertNull($calloutArr);
    }

    public function testBuildCalloutSessionPoolResponse(): void
    {
        $kernel = self::bootKernel();
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        $calloutArr = $calloutSessionAPIService->getCalloutPool();
        self::assertEquals(
            [
                'calls' => [
                    [
                        'state' => 'INITIATED',
                        'call_number' => '987654321012',
                        'sip_room_number' => '12340',
                        'sip_pin' => '987654321',
                        'display_name' => 'Sie wurden von Test1, 1234, User, Test eingeladen',
                        'tag' => null,
                        'organisator' => 'Test1, 1234, User, Test',
                        'title' => 'TestMeeting: 0',
                        'links' => ['dial' => '/api/v1/call/out/dial/ksdlfjlkfds']
                    ]
                ]
            ],
            $calloutArr
        );
    }
    public function testBuildCalloutDialPoolResponse(): void
    {
        $kernel = self::bootKernel();
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        $calloutArr = $calloutSessionAPIService->getDialPool();
        self::assertEquals(
            [
                'calls' => [
                    [
                        'state' => 'DIALED',
                        'call_number' => '987654321012',
                        'sip_room_number' => '12340',
                        'sip_pin' => '987654321',
                        'display_name' => 'Sie wurden von Test1, 1234, User, Test eingeladen',
                        'tag' => null,
                        'organisator' => 'Test1, 1234, User, Test',
                        'title' => 'TestMeeting: 0',
                        'links' => ['dial' => '/api/v1/call/out/dial/ksdlfjlkfdfgsdds']
                    ]
                ]
            ],
            $calloutArr
        );
    }
    public function testBuildCalloutOnHoldPoolResponse(): void
    {
        $kernel = self::bootKernel();
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        $calloutArr = $calloutSessionAPIService->getOnHoldPool();
        self::assertEquals(
            [
                'calls' => [
                    [
                        'state' => 'ON_HOLD',
                        'call_number' => '987654321012',
                        'sip_room_number' => '12340',
                        'sip_pin' => '987654321',
                        'display_name' => 'Sie wurden von Test1, 1234, User, Test eingeladen',
                        'tag' => null,
                        'organisator' => 'Test1, 1234, User, Test',
                        'title' => 'TestMeeting: 0',
                        'links' => ['dial' => '/api/v1/call/out/dial/ksddfglfjlkfds']
                    ]
                ]
            ],
            $calloutArr
        );
    }
}
