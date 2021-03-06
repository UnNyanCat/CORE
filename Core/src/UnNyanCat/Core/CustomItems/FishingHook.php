<?php

namespace UnNyanCat\Core\CustomItems;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\Level;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Random;

use UnNyanCat\Core\Main;

class FishingHook extends Projectile
{
    public const NETWORK_ID = self::FISHING_HOOK;

    public $height = 0.25;
    public $width = 0.25;
    protected $gravity = 0.1;

    public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner = null){
        parent::__construct($level, $nbt, $owner);
        if($owner instanceof Player){
            $this->setPosition($this->add(0, $owner->getEyeHeight() - 0.1));
            $this->setMotion($owner->getDirectionVector()->multiply(0.4));
            Main::setFishingHook($this, $owner);
            $this->handleHookCasting($this->motion->x, $this->motion->y, $this->motion->z, 1.5, 1.0);
        }
    }

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
        //Do nothing
    }

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
        parent::onHitBlock($blockHit, $hitResult);
    }

    public function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2){
        $rand = new Random();
        $f = sqrt($x * $x + $y * $y + $z * $z);
        $x = $x / (float) $f;
        $y = $y / (float) $f;
        $z = $z / (float) $f;
        $x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
        $y = $y + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
        $z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
        $x = $x * (float) $f1;
        $y = $y * (float) $f1;
        $z = $z * (float) $f1;
        $this->motion->x += $x;
        $this->motion->y += $y;
        $this->motion->z += $z;
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $owner = $this->getOwningEntity();
        if($owner instanceof Player){
            if(!$owner->getInventory()->getItemInHand() instanceof FishingRod or !$owner->isAlive() or $owner->isClosed()) $this->flagForDespawn();
        }else $this->flagForDespawn();

        return $hasUpdate;
    }

    public function close() : void{
        parent::close();

        $owner = $this->getOwningEntity();
        if($owner instanceof Player){
            Main::setFishingHook(null, $owner);
        }
    }

    public function handleHookRetraction() : void{
        $owner = $this->getOwningEntity();
        $dist = $this->distanceSquared($owner);
        $owner->setMotion($this->subtract($owner)->multiply($this->getGrapplingSpeed($dist)));
        $this->flagForDespawn();
    }

    private function getGrapplingSpeed(float $dist) : float{
        if($dist > 600):
            $motion = 0.26;
        elseif($dist > 500):
            $motion = 0.24;
        elseif($dist > 300):
            $motion = 0.23;
        elseif($dist > 200):
            $motion = 0.201;
        elseif($dist > 100):
            $motion = 0.17;
        elseif($dist > 40):
            $motion = 0.11;
        else:
            $motion = 0.8;
        endif;

        return $motion;
    }

    public function applyGravity() : void{
        if($this->isUnderwater()) $this->motion->y += $this->gravity;
        else parent::applyGravity();
    }
}