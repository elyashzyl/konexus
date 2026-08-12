<?php

namespace App\Enums;

/**
 * The rounding rules that may be applied to a grade scale.
 */
enum RoundingRule: string
{
    case STANDARD = 'standard';
    case HALF_UP = 'half-up';
    case CEIL = 'ceil';
    case FLOOR = 'floor';

    /**
     * Human readable label for the rule.
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::HALF_UP => 'Half Up',
            self::CEIL => 'Ceiling',
            self::FLOOR => 'Floor',
        };
    }

    /**
     * All rules as [value, label] pairs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $rule) => [
                'value' => $rule->value,
                'label' => $rule->label(),
            ],
            self::cases()
        );
    }
}