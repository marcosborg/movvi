<?php

namespace App\Support;

class InspectionRoutineConfig
{
    public static function options(): array
    {
        return [
            'documents' => [
                'dua' => 'DUA',
                'insurance' => 'Seguro',
                'inspection_periodic' => 'Inspecao periodica',
                'tvde_stickers' => 'Disticos TVDE',
                'no_smoking_sticker' => 'Autocolante proibicao de fumar',
            ],
            'operational_checks' => [
                'cleanliness' => 'Limpeza (interior/exterior)',
                'fuel_energy' => 'Combustivel/Energia',
                'mileage' => 'Quilometragem',
                'tire_condition' => 'Estado dos pneus',
                'panel_warnings' => 'Avisos no painel',
            ],
            'accessories' => [
                'via_verde' => 'Via Verde',
                'charging_cable' => 'Cabos carregamento',
                'charging_adapter' => 'Adaptadores carregamento',
                'spare_tire' => 'Pneu suplente',
                'anti_puncture_kit' => 'Kit anti-furos',
                'jack_wrench' => 'Macaco e chave de rodas',
                'warning_triangle' => 'Triangulo sinalizacao',
                'reflective_vest' => 'Colete refletor',
            ],
            'exterior_slots' => (array) config('inspections.slot_labels.exterior', []),
            'interior_slots' => (array) config('inspections.slot_labels.interior', []),
        ];
    }

    public static function defaults(): array
    {
        $options = self::options();

        return [
            'documents' => array_keys($options['documents']),
            'operational_checks' => array_keys($options['operational_checks']),
            'accessories' => array_keys($options['accessories']),
            'exterior_slots' => array_keys($options['exterior_slots']),
            'interior_slots' => array_keys($options['interior_slots']),
        ];
    }

    public static function sanitize(?array $input): array
    {
        $defaults = self::defaults();
        $options = self::options();
        $source = $input ?: [];

        return [
            'documents' => self::sanitizeList($source['documents'] ?? $defaults['documents'], array_keys($options['documents'])),
            'operational_checks' => self::sanitizeList($source['operational_checks'] ?? $defaults['operational_checks'], array_keys($options['operational_checks'])),
            'accessories' => self::sanitizeList($source['accessories'] ?? $defaults['accessories'], array_keys($options['accessories'])),
            'exterior_slots' => self::sanitizeList($source['exterior_slots'] ?? $defaults['exterior_slots'], array_keys($options['exterior_slots'])),
            'interior_slots' => self::sanitizeList($source['interior_slots'] ?? $defaults['interior_slots'], array_keys($options['interior_slots'])),
        ];
    }

    /**
     * @param mixed $values
     * @param string[] $allowed
     * @return string[]
     */
    private static function sanitizeList($values, array $allowed): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            $item = (string) $value;
            if (in_array($item, $allowed, true)) {
                $normalized[] = $item;
            }
        }

        return array_values(array_unique($normalized));
    }
}

