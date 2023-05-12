<?php

namespace App\Tests\callOut;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Callout\CallOutSessionAPIDialService;
use App\Service\Callout\CalloutSessionAPIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalloutAPIDialServiceTest extends KernelTestCase
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
        $callerUserId = new CallerId();
        $callerUserId->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setUser($user)
            ->setCallerId('987654321');
        $manager->persist($callerUserId);
        $manager->flush();
    }

    public function testDialInSuccess(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $calloutDialService = self::getContainer()->get(CallOutSessionAPIDialService::class);
        self::assertEquals(
            ['status' => 'OK', 'links' => [
                'accept' => '/api/v1/lobby/sip/pin/12340?caller_id=987654321012&pin=987654321',
                'refuse' => '/api/v1/call/out/refuse/ksdlfjlkfds',
                'timeout' => '/api/v1/call/out/timeout/ksdlfjlkfds',
                'error' => '/api/v1/call/out/error/ksdlfjlkfds',
                'later' => '/api/v1/call/out/later/ksdlfjlkfds',
                'dial' => '/api/v1/call/out/dial/ksdlfjlkfds',
                'occupied' => '/api/v1/call/out/occupied/ksdlfjlkfds',
                'ringing' => '/api/v1/call/out/ringing/ksdlfjlkfds',
                'unreachable' => '/api/v1/call/out/unreachable/ksdlfjlkfds'
            ]],
            $calloutDialService->dialSession('ksdlfjlkfds')
        );
    }



    public function testDialInNoSucess(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $calloutDialService = self::getContainer()->get(CallOutSessionAPIDialService::class);
        self::assertEquals(['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'], $calloutDialService->dialSession('invalid'));
    }

    public function testDialAndPool(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $calloutDialService = self::getContainer()->get(CallOutSessionAPIDialService::class);
        $calloutSessionAPIService = self::getContainer()->get(CalloutSessionAPIService::class);
        self::assertEquals(1, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$INITIATED)));
        self::assertEquals(0, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$DIALED)));
        $calloutDialService->dialSession('ksdlfjlkfds');
        self::assertEquals(0, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$INITIATED)));
        self::assertEquals(1, sizeof($calloutSessionAPIService->findCalloutSessionByState(CalloutSession::$DIALED)));
    }
    public function testDialWrongState(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $calloutDialService = self::getContainer()->get(CallOutSessionAPIDialService::class);
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $callout = $calloutRepo->findOneBy(['uid' => 'ksdlfjlkfds']);
        $callout->setState(CalloutSession::$ON_HOLD);
        self::assertEquals(['error' => true, 'reason' => 'SESSION_NOT_IN_CORRECT_STATE'], $calloutDialService->dialSession('ksdlfjlkfds'));
    }
}
