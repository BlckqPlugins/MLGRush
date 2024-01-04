<?php
namespace MLGRush\Game;

use MLGRush\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\player\Player;
use pocketmine\Server;

class GameListener implements Listener{
    public $manager;

    public function __construct(GameManager $manager){
        $this->manager = $manager;
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if ($this->manager->isInGame($player) && !is_null($this->manager->getGameByPlayer($player))) {
            $game = $this->manager->getGameByPlayer($player);
            $game->broadcastMessage("§cYour enemy §e{$player->getDisplayName()} §cleft the game.");
            if ($game->redplayer === $player) {
                $winner = $game->blueplayer;
            } else {
                $winner = $game->redplayer;
            }
            $game->endGame($winner);
            $event->setQuitMessage("");
        }
    }

    public function onBlockBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        if ($this->manager->isInGame($player) && !is_null($this->manager->getGameByPlayer($player))) {
            $block = $event->getBlock();

            $id = $block->getTypeId();
            $event->setDrops([]);
            $game = $this->manager->getGameByPlayer($player);
            if ($id == VanillaBlocks::BED()->getTypeId()) {
                if ($block->getPosition()->distance($game->redspawn) > $block->getPosition()->distance($game->bluespawn)) {
                    if ($player->getName() !== $game->blueplayer->getName()) {
                        $game->brokeBed($player);
                        $event->cancel();
                    }
                } else {
                    if ($player->getName() !== $game->redplayer->getName()) {
                        $game->brokeBed($player);
                        $event->cancel();
                    }
                }
                $event->cancel();
            } else {
                $pos = $block->getPosition()->asVector3();
                $arr = [$pos->getX(), $pos->getY(), $pos->getZ()];
                if (in_array($arr, $game->blocks)) {
                    $k = array_search($arr, $game->blocks);
                    unset($game->blocks[$k]);
                    $event->setDrops([]);
                } else {
                    $event->cancel();
                }
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        if ($this->manager->isInGame($player) && !is_null($this->manager->getGameByPlayer($player))) {
            foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]){
                $item = $event->getItem();

                $game = $this->manager->getGameByPlayer($player);

                if ($item->getTypeId() === VanillaBlocks::SANDSTONE()->asItem()->getTypeId()) {
                    $inv = $player->getInventory();
                    $contents = $inv->getContents();
                    foreach ($contents as $content) {
                        if ($content->getTypeId() === VanillaBlocks::SANDSTONE()->asItem()->getTypeId()) {
                            if ($content->getCount() < 64) {
                                $slots = $game->db->getSlots($player);
                                $item = VanillaBlocks::SANDSTONE()->asItem();
                                $player->getInventory()->addItem($item);
                            }
                        }
                    }
                }

                $game = $this->manager->getGameByPlayer($player);
                if ($block->getPosition()->y >= $game->maxheight or $block->getPosition()->y < $game->minheight) {
                    $event->cancel();
                    $player->sendMessage(Main::getInstance()->prefix . "§cYou can't place blocks here.");
                } else if ($block->getPosition()->add(0.5, 0, 0.5)->distance($game->redspawn) < 0.55 or $block->getPosition()->add(0.5, 0, 0.5)->distance($game->bluespawn) < 0.55) {
                    $event->cancel();
                    $player->sendMessage(Main::getInstance()->prefix . "§cYou can't place blocks at the spawn.");
                } else if ($block->getPosition()->add(0.5, 0, 0.5)->distance($game->redspawn->add(0, 1, 0)) < 0.55 or $block->getPosition()->add(0.5, 0, 0.5)->distance($game->bluespawn->add(0, 1, 0)) < 0.55) {
                    $event->cancel();
                    $player->sendMessage(Main::getInstance()->prefix . "§cYou can't place blocks at the spawn.");
                } else {
                    $game->blocks[] = [$block->getPosition()->x, $block->getPosition()->y, $block->getPosition()->z];
                }
            }
        }
    }

    public function onChange(BlockBurnEvent $event){
        $event->cancel();
    }

    /**
     * @param EntityDamageEvent $event
     * @priority MONITOR
     */
    public function onDamage(EntityDamageEvent $event){
        $player = $event->getEntity();
        $player->setHealth(20.0);
        if ($player instanceof Player) {
            if ($this->manager->isInGame($player)) {
                if ($event instanceof EntityDamageByEntityEvent) {
                    $attacker = $event->getDamager();
                    $player->setHealth(20.0);
                    $attacker->setHealth(20.0);
                } else {
                    $event->cancel();
                }
            } else {
                $event->cancel();
                if ($event->getCause() === $event::CAUSE_VOID){
                    $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                }
            }
        }
    }

    public function onItemUse(PlayerItemUseEvent $event){
        $item = $event->getItem();
        if ($item->hasEnchantment(VanillaEnchantments::KNOCKBACK()) || $item instanceof Throwable) $event->cancel();
    }

    public function onConsume(PlayerItemConsumeEvent $event){
        $event->cancel();
    }

    public function onFarm(EntityTrampleFarmlandEvent $event){
        $event->cancel();
    }

    public function onUpdate(BlockUpdateEvent $event){
        $event->cancel();
    }
}