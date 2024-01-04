<?php

namespace MLGRush\player;

use MLGRush\Main;
use pocketmine\player\Player;

class PlayerSession {
    protected int $stick = 1;
    protected int $blocks = 2;
    protected int $pickaxe = 3;

    /** @var null|Player */
    protected ?Player $challenged = null;

    public function __construct(private readonly Player $player) {}

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @param int $stick
     */
    public function setStick(int $stick): void
    {
        $this->stick = $stick;
    }

    /**
     * @param int $blocks
     */
    public function setBlocks(int $blocks): void
    {
        $this->blocks = $blocks;
    }

    /**
     * @param int $pickaxe
     */
    public function setPickaxe(int $pickaxe): void
    {
        $this->pickaxe = $pickaxe;
    }

    /**
     * @return int
     */
    public function getStick(): int
    {
        return $this->stick;
    }

    /**
     * @return int
     */
    public function getPickaxe(): int
    {
        return $this->pickaxe;
    }

    /**
     * @return int
     */
    public function getBlocks(): int
    {
        return $this->blocks;
    }

    /**
     * @return Player|null
     */
    public function getChallenger(): ?Player
    {
        return $this->challenged;
    }

    public function challenge(?Player $challenger = null): void{
        if ($challenger instanceof Player && !$challenger->isSpectator() && !$challenger->isClosed() && $challenger->isOnline()) {
            if ($this->challenged instanceof Player && $this->challenged->isOnline() && !$this->challenged->isClosed() && !$this->challenged->isSpectator()) {
                if ($this->challenged->getName() === $challenger->getName()) {
                    $challenger = null;
                    $this->challenged->sendTip("§aChallenged");
                    $this->getPlayer()->sendTip("§aChallenged");
                } else {
                    $this?->getPlayer()->sendMessage(Main::getInstance()->prefix . "§aYou have challenged§6 {$this->challenged->getDisplayName()}§r.");
                    $challenger?->sendMessage(Main::getInstance()->prefix . "§aYou have been challenged by§6 {$this->getPlayer()->getDisplayName()}§r.");
                }
            } else {
                $challenger?->sendMessage(Main::getInstance()->prefix . "§aYou have challenged§6 {$this->getPlayer()->getDisplayName()}§r.");
                $this?->getPlayer()->sendMessage(Main::getInstance()->prefix . "§aYou have been challenged by§6 {$this->challenged->getDisplayName()}§r.");
            }
        }
        $this->challenged = $challenger;
    }
}