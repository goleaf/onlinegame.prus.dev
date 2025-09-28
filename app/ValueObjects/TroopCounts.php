<?php

namespace App\ValueObjects;

readonly class TroopCounts
{
    public function __construct(
        public int $spearmen = 0,
        public int $swordsmen = 0,
        public int $archers = 0,
        public int $cavalry = 0,
        public int $mountedArchers = 0,
        public int $catapults = 0,
        public int $rams = 0,
        public int $spies = 0,
        public int $settlers = 0
    ) {
    }

    /**
     * Get total troop count
     */
    public function getTotal(): int
    {
        return $this->spearmen + $this->swordsmen + $this->archers
            + $this->cavalry + $this->mountedArchers + $this->catapults
            + $this->rams + $this->spies + $this->settlers;
    }

    /**
     * Get total infantry count
     */
    public function getInfantryCount(): int
    {
        return $this->spearmen + $this->swordsmen + $this->archers;
    }

    /**
     * Get total cavalry count
     */
    public function getCavalryCount(): int
    {
        return $this->cavalry + $this->mountedArchers;
    }

    /**
     * Get total siege count
     */
    public function getSiegeCount(): int
    {
        return $this->catapults + $this->rams;
    }

    /**
     * Get total support count
     */
    public function getSupportCount(): int
    {
        return $this->spies + $this->settlers;
    }

    /**
     * Add troop counts
     */
    public function add(TroopCounts $other): self
    {
        return new self(
            spearmen: $this->spearmen + $other->spearmen,
            swordsmen: $this->swordsmen + $other->swordsmen,
            archers: $this->archers + $other->archers,
            cavalry: $this->cavalry + $other->cavalry,
            mountedArchers: $this->mountedArchers + $other->mountedArchers,
            catapults: $this->catapults + $other->catapults,
            rams: $this->rams + $other->rams,
            spies: $this->spies + $other->spies,
            settlers: $this->settlers + $other->settlers
        );
    }

    /**
     * Subtract troop counts
     */
    public function subtract(TroopCounts $other): self
    {
        return new self(
            spearmen: max(0, $this->spearmen - $other->spearmen),
            swordsmen: max(0, $this->swordsmen - $other->swordsmen),
            archers: max(0, $this->archers - $other->archers),
            cavalry: max(0, $this->cavalry - $other->cavalry),
            mountedArchers: max(0, $this->mountedArchers - $other->mountedArchers),
            catapults: max(0, $this->catapults - $other->catapults),
            rams: max(0, $this->rams - $other->rams),
            spies: max(0, $this->spies - $other->spies),
            settlers: max(0, $this->settlers - $other->settlers)
        );
    }

    /**
     * Check if army is empty
     */
    public function isEmpty(): bool
    {
        return $this->getTotal() === 0;
    }

    /**
     * Get army composition as percentages
     */
    public function getComposition(): array
    {
        $total = $this->getTotal();
        if ($total === 0) {
            return [
                'infantry' => 0,
                'cavalry' => 0,
                'siege' => 0,
                'support' => 0,
            ];
        }

        return [
            'infantry' => round(($this->getInfantryCount() / $total) * 100, 2),
            'cavalry' => round(($this->getCavalryCount() / $total) * 100, 2),
            'siege' => round(($this->getSiegeCount() / $total) * 100, 2),
            'support' => round(($this->getSupportCount() / $total) * 100, 2),
        ];
    }

    /**
     * Get army type (balanced, infantry-heavy, cavalry-heavy, etc.)
     */
    public function getArmyType(): string
    {
        $composition = $this->getComposition();

        if ($composition['infantry'] >= 60) {
            return 'infantry-heavy';
        }
        if ($composition['cavalry'] >= 60) {
            return 'cavalry-heavy';
        }
        if ($composition['siege'] >= 30) {
            return 'siege-focused';
        }
        if ($composition['support'] >= 20) {
            return 'support-heavy';
        }

        return 'balanced';
    }

    /**
     * Check if army is balanced
     */
    public function isBalanced(): bool
    {
        return $this->getArmyType() === 'balanced';
    }

    /**
     * Get troop counts as array
     */
    public function toArray(): array
    {
        return [
            'spearmen' => $this->spearmen,
            'swordsmen' => $this->swordsmen,
            'archers' => $this->archers,
            'cavalry' => $this->cavalry,
            'mounted_archers' => $this->mountedArchers,
            'catapults' => $this->catapults,
            'rams' => $this->rams,
            'spies' => $this->spies,
            'settlers' => $this->settlers,
            'total' => $this->getTotal(),
            'infantry_count' => $this->getInfantryCount(),
            'cavalry_count' => $this->getCavalryCount(),
            'siege_count' => $this->getSiegeCount(),
            'support_count' => $this->getSupportCount(),
            'composition' => $this->getComposition(),
            'army_type' => $this->getArmyType(),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            spearmen: $data['spearmen'] ?? 0,
            swordsmen: $data['swordsmen'] ?? 0,
            archers: $data['archers'] ?? 0,
            cavalry: $data['cavalry'] ?? 0,
            mountedArchers: $data['mounted_archers'] ?? 0,
            catapults: $data['catapults'] ?? 0,
            rams: $data['rams'] ?? 0,
            spies: $data['spies'] ?? 0,
            settlers: $data['settlers'] ?? 0
        );
    }
}
