<?php

declare(strict_types=1);

namespace SpinningDeath;

use pocketmine\world\World;
use pocketmine\entity\Skin;
use pocketmine\entity\Human;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\entity\EntityFactory;
use pocketmine\command\CommandSender;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\EntityDataHelper;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Main extends PluginBase implements Listener {

    private Config $deathRecord;

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        $this->deathRecord = new Config($this->getDataFolder() . "deaths.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        EntityFactory::getInstance()->register(DeathEntity::class, function (World $world, CompoundTag $nbt): DeathEntity {
            return new DeathEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ['DeathEntity']);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "spinningdeath") {
            if ($sender instanceof Player) {
                if ($sender->getServer()->isOp($sender->getName())) {
                    if (!isset($args[0])) {
                        $sender->sendMessage("§aUsage: /spinningdeath spawn|remove");
                        return false;
                    }
                    if ($args[0] === "spawn") {
                        $this->spawnDeathEntity($sender);
                        $sender->sendMessage("§bSpinning death entity spawned.");
                    } elseif ($args[0] === "remove") {
                        $deathEntity = $this->getNearSpinningDeath($sender);

                        if ($deathEntity !== null) {
                            $deathEntity->flagForDespawn();
                            $sender->sendMessage("§bSpinning death entity removed.");
                            return true;
                        }
                        $sender->sendMessage("§cNo spinning death entity found.");
                    }
                }
            }
        }
        return true;
    }

    public function getNearSpinningDeath(Player $player): ?DeathEntity {
        $level = $player->getWorld();

        foreach ($level->getEntities() as $entity) {
            if ($entity instanceof DeathEntity) {
                if ($player->getPosition()->distance($entity->getPosition()) <= 5 && $entity->getPosition()->distance($player->getPosition()) > 0) {
                    return $entity;
                }
            }
        }
        return null;
    }

    public function spawnDeathEntity(Player $sender): void {
        $path = $this->getFile() . "resources/texture.png";
        $img = @imagecreatefrompng($path);
        $skinBytes = "";
        $s = (int)@getimagesize($path)[1];

        for ($y = 0; $y < $s; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $color = @imagecolorat($img, $x, $y);
                $a = ((~($color >> 24)) << 1) & 0xff;
                $r = ($color >> 16) & 0xff;
                $g = ($color >> 8) & 0xff;
                $b = $color & 0xff;
                $skinBytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);

        $skin = new Skin($sender->getSkin()->getSkinId(), $skinBytes, '', 'geometry.giraffe', file_get_contents($this->getFile() . 'resources/giraffe.json'));
        $entity = new DeathEntity($sender->getLocation(), $skin);
        $nameTag = $this->getConfig()->get("nametag", "&eHey &a{player} Estas Jugando\n &4Sexo&0Craft  &bFactions &d Tu Total de Muertes es\n &g{deaths} Muertes");
        $entity->setNameTag($nameTag);
        $entity->setNameTagAlwaysVisible();
        $entity->setNameTagVisible();
        $entity->spawnToAll();
    }

    public function onJoin(PlayerJoinEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$this->deathRecord->get($player->getName())) {
            $this->deathRecord->set($player->getName(), 0);
            $this->deathRecord->save();
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $ev): void {
        $player = $ev->getPlayer();
        $this->addDeath($player);
    }

    public function addDeath(Player $player): void {
        $this->deathRecord->set($player->getName(), $this->deathRecord->get($player->getName()) + 1);
        $this->deathRecord->save();
    }

    public function getDeathCount(Player $player): int {
        return $this->deathRecord->get($player->getName(), 0);
    }
}
