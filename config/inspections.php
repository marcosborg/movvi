<?php

return [
    'types' => [
        'handover' => 'handover',
        'return' => 'return',
    ],

    'type_labels' => [
        'handover' => 'Entrega',
        'return' => 'Recolha',
        'initial' => 'Inicial',
        'routine' => 'Rotina',
        'fleet_exit' => 'Saida da frota',
    ],

    'statuses' => [
        'draft' => 'draft',
        'in_progress' => 'in_progress',
        'ready_to_sign' => 'ready_to_sign',
        'signed' => 'signed',
        'closed' => 'closed',
    ],

    'status_labels' => [
        'draft' => 'Rascunho',
        'in_progress' => 'Em progresso',
        'ready_to_sign' => 'Pronta para assinar',
        'signed' => 'Assinada',
        'closed' => 'Fechada',
    ],

    'step_labels' => [
        1 => 'Identificacao da viatura',
        2 => 'Identificacao do condutor',
        3 => 'Documentacao',
        4 => 'Estado operacional',
        5 => 'Acessorios e extras',
        6 => 'Fotografias exteriores',
        7 => 'Fotografias interiores',
        8 => 'Danos exteriores',
        9 => 'Danos interiores',
        10 => 'Extras e observacoes',
        11 => 'Assinaturas',
        12 => 'Fecho e PDF',
    ],

    'required_slots' => [
        'exterior' => [
            'front',
            'front_left_45',
            'left',
            'rear_left_45',
            'rear',
            'rear_right_45',
            'right',
            'front_right_45',
        ],
        'interior' => [
            'dashboard',
            'odometer',
            'center_console',
            'front_seats',
            'rear_seats',
            'trunk',
        ],
    ],

    'slot_labels' => [
        'exterior' => [
            'front' => 'Frente',
            'front_left_45' => 'Frente esquerda 45 graus',
            'left' => 'Lado esquerdo',
            'rear_left_45' => 'Traseira esquerda 45 graus',
            'rear' => 'Traseira',
            'rear_right_45' => 'Traseira direita 45 graus',
            'right' => 'Lado direito',
            'front_right_45' => 'Frente direita 45 graus',
        ],
        'interior' => [
            'dashboard' => 'Tablier',
            'front_seats' => 'Bancos dianteiros',
            'rear_seats' => 'Bancos traseiros',
            'trunk' => 'Bagageira',
            'odometer' => 'Odometro',
            'center_console' => 'Consola central',
        ],
    ],

    'damage_locations' => [
        'body' => 'Carrocaria',
        'interior' => 'Interior',
        'tires_rims' => 'Pneus/Jantes',
        'glass' => 'Vidros',
        'other' => 'Outros',
    ],

    'damage_types' => [
        'scratch' => 'Risco',
        'dent' => 'Amolgadela',
        'crack' => 'Racha',
        'broken' => 'Partido',
        'stain' => 'Mancha',
        'tear' => 'Rasgao',
        'missing' => 'Em falta',
        'other' => 'Outro',
    ],

    'damage_parts' => [
        'body' => [
            'label' => 'Carrocaria',
            'sections' => [
                'front_bumper' => 'Para-choques dianteiro',
                'rear_bumper' => 'Para-choques traseiro',
                'hood' => 'Capo',
                'roof' => 'Tejadilho',
                'front_left_door' => 'Porta dianteira esquerda',
                'front_right_door' => 'Porta dianteira direita',
                'rear_left_door' => 'Porta traseira esquerda',
                'rear_right_door' => 'Porta traseira direita',
                'front_left_fender' => 'Guarda-lamas dianteiro esquerdo',
                'front_right_fender' => 'Guarda-lamas dianteiro direito',
                'rear_left_quarter_panel' => 'Painel lateral traseiro esquerdo',
                'rear_right_quarter_panel' => 'Painel lateral traseiro direito',
            ],
        ],
        'interior' => [
            'label' => 'Interior',
            'sections' => [
                'driver_seat' => 'Banco do condutor',
                'passenger_seat' => 'Banco do passageiro',
                'rear_seats' => 'Bancos traseiros',
                'dashboard' => 'Tablie',
                'center_console' => 'Consola central',
                'door_panel_driver_passenger' => 'Painel de porta (condutor / passageiro)',
                'headliner' => 'Teto interior',
                'floor_mats' => 'Tapetes',
                'trunk' => 'Bagageira',
            ],
        ],
        'tires_rims' => [
            'label' => 'Pneus/Jantes',
            'sections' => [
                'front_left_tire' => 'Pneu dianteiro esquerdo',
                'front_right_tire' => 'Pneu dianteiro direito',
                'rear_left_tire' => 'Pneu traseiro esquerdo',
                'rear_right_tire' => 'Pneu traseiro direito',
                'front_left_rim' => 'Jante dianteira esquerda',
                'front_right_rim' => 'Jante dianteira direita',
                'rear_left_rim' => 'Jante traseira esquerda',
                'rear_right_rim' => 'Jante traseira direita',
            ],
        ],
        'glass' => [
            'label' => 'Vidros',
            'sections' => [
                'windshield' => 'Para-brisas',
                'rear_window' => 'Vidro traseiro',
                'front_left_window' => 'Vidro lateral dianteiro esquerdo',
                'front_right_window' => 'Vidro lateral dianteiro direito',
                'rear_left_window' => 'Vidro lateral traseiro esquerdo',
                'rear_right_window' => 'Vidro lateral traseiro direito',
                'left_mirror' => 'Espelho retrovisor esquerdo',
                'right_mirror' => 'Espelho retrovisor direito',
            ],
        ],
        'other' => [
            'label' => 'Outros',
            'sections' => [
                'antenna' => 'Antena',
                'sensors' => 'Sensores',
                'camera' => 'Camara',
                'grilles' => 'Grelhas',
                'unlisted_items' => 'Elementos nao listados',
            ],
        ],
    ],
];
