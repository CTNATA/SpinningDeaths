<?php

declare(strict_types=1);

namespace SpinningDeath;

use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class DeathEntity extends Human {

    public function onUpdate(int $currentTick): bool {
        $this->location->yaw += 5.5;
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        $this->updateMovement();

        foreach ($this->getViewers() as $viewer) {
            $loader = Server::getInstance()->getPluginManager()->getPlugin("SpinningDeath");
            if ($loader instanceof Main) {
                $deaths = $loader->getDeathCount($viewer);
                $nameTag = $loader->getConfig()->get("nametag", "&cHey &0{player} Estas Jugando\n &4Sexo&0Craft  &4Factions &c Tu Total de Muertes es\n &4{deaths} Muertes");
                $formattedNameTag = TextFormat::colorize(str_replace(["{deaths}", "{player}"], [$deaths, $viewer->getName()], $nameTag));
                $this->setNameTag($formattedNameTag);
            }
        }
        return parent::onUpdate($currentTick);
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();
    }
}
