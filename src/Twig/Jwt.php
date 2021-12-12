<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\MessageService;
use App\Service\RoomService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class Jwt extends AbstractExtension
{
    private $paramterBag;
    private $roomService;
    public function __construct(RoomService $roomService, ParameterBagInterface $parameterBag)
    {
        $this->paramterBag = $parameterBag;
        $this->roomService = $roomService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('jwtFromRoom', [$this, 'jwtFromRoom']),
        ];
    }
    public function jwtFromRoom(User $user,Rooms $rooms, $name)
    {
        return $this->roomService->generateJwt($rooms,$user,$name);
    }
}