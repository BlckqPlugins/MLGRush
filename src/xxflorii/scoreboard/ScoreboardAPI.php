<?php

namespace xxflorii\scoreboard;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

class ScoreboardAPI
{
    private const DISPLAY_TYPE_SIDEBAR = "sidebar";
    private const SCOREBOARD_TYPE_DUMMY = "dummy";
    public function create(Player $player, string $title): void {
        $packet = SetDisplayObjectivePacket::create(self::DISPLAY_TYPE_SIDEBAR, $player->getName(), " {$title} ", self::SCOREBOARD_TYPE_DUMMY, 0);
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public function remove(Player $player): void {
        $packet = RemoveObjectivePacket::create($player->getName());
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public function setLine(Player $player, int $line, string $text): void {
        $entry = new ScorePacketEntry();
        $entry->scoreboardId = $line;
        $entry->objectiveName = $player->getName();
        $entry->score = $line;
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = " {$text} ";

        $packet = SetScorePacket::create(SetScorePacket::TYPE_CHANGE, [$entry]);
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public function removeLine(Player $player, int $line): void {
        $entry = new ScorePacketEntry();
        $entry->scoreboardId = $line;
        $entry->objectiveName = $player->getName();
        $entry->score = $line;
        $entry->type = $entry::TYPE_FAKE_PLAYER;

        $packet = SetScorePacket::create(SetScorePacket::TYPE_REMOVE, [$entry]);
        $player->getNetworkSession()->sendDataPacket($packet);
    }
    public function updateTitle(Player $player, string $newTitle): void {
        $packet = SetDisplayObjectivePacket::create(
            self::DISPLAY_TYPE_SIDEBAR,
            $player->getName(),
            " {$newTitle} ",
            self::SCOREBOARD_TYPE_DUMMY,
            0
        );
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public function clearAllLines(Player $player): void {
        for ($line = 1; $line <= 15; $line++) {
            $this->removeLine($player, $line);
        }
    }

}