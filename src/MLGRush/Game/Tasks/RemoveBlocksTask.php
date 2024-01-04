<?php

namespace MLGRush\Game\Tasks;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldException;

class RemoveBlocksTask extends Task
{
    public $level;
    public $blocks = [];

    public function __construct(World $level, array $blocks)
    {
        $this->level = $level;
        $this->blocks = $blocks;
    }

    public function onRun(): void
    {
        if ($this->level instanceof World && $this->level->isLoaded()) {
            $blocks = $this->blocks;

            foreach ($blocks as $block) {
                if (isset($block[0]) && isset($block[1]) && isset($block[2]) && $this->level->isInWorld($block[0], $block[1], $block[2])) {
                    try {
                        $this->level->setBlockAt($block[0], $block[1], $block[2], VanillaBlocks::AIR());
                    } catch (WorldException $exception){
                        Server::getInstance()->getLogger()->logException($exception);
                    }
                }
            }
            foreach ($blocks as $value) {
                $key = array_search($value, $this->blocks);
                unset($this->blocks[$key]);
            }
        }
        //Fuck this GameManager::get()->plugin->getScheduler()->cancelTask($this->getHandler()->getTaskId());
        $this->getHandler()->cancel();
    }
}