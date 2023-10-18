<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Enums\FormThemeTemplateTypesEnum;
use App\FormThemeTemplate;
use Facades\App\Services\Util;
use Illuminate\Support\Facades\Storage;

class FormThemeTemplateTableSeeder extends Seeder
{
    protected $themes = [
        [
            'name' => 'Blue theme',
             'config' => [
                'general' => [
                    'colors' => [
                        'active_color' => '#2196f3'
                    ],
                    'font' => [
                        'family' => 'Lato'
                    ]
                ],
                'typography' => [
                    'question_title' => [
                        'font' => [
                            'family' => 'Lato'
                        ]
                    ],
                    'question_description' => [
                        'font' => [
                            'family' => 'Lato'
                        ]
                    ],
                    'input_box' => [
                        'font' => [
                            'family' => 'Lato',
                            'color' => '#2196f3'
                        ]
                    ]
                ],
                'ui_elements' => [
                    'step_navigation' => [
                        'back_button' => [
                            'backgroundColor' => '#d3d3d3',
                            'font' => [
                                'family' => 'Lato'
                            ]
                        ],
                        'next_button' => [
                            'backgroundColor' => '#2196f3',
                            'font' => [
                                'family' => 'Lato'
                            ]
                        ],
                        'submit_button' => [
                            'backgroundColor' => '#2196f3',
                            'font' => [
                                'family' => 'Lato'
                            ],
                        ]
                    ],
                    'radio_checkbox' => [
                        'checked_color' => '#2196f3',
                        'hover_color' => '#2196f3',
                        'radius' => '5'
                    ],
                    'choice' => [
                        'image_icon_skin' => [
                            'hover_style' => [
                                'border' => [
                                    'color' => '#2196f3',
                                ],
                            ],
                            'active_style' => [
                                'border' => [
                                    'color' => '#2196f3',
                                ],
                            ],
                        ]
                    ],
                ],
                'custom_css' => '',
            ],
        ],
        [
            'name' => 'Violet  theme',
            'config' => [
                'general' => [
                    'colors' => [
                        'active_color' => '#6A1BA2'
                    ],
                    'font' => [
                        'family' => 'Poppins'
                    ]
                ],
                'typography' => [
                    'question_title' => [
                        'font' => [
                            'family' => 'Poppins'
                        ]
                    ],
                    'question_description' => [
                        'font' => [
                            'family' => 'Poppins'
                        ]
                    ],
                    'input_box' => [
                        'border' => [
                            'skin' => 'all',
                            'style' => 'Solid',
                            'width' => '1',
                            'color' => '#ccc',
                        ],
                        'font' => [

                            'family' => 'Poppins',
                            'color' => '#6A1BA2',
                        ]
                    ]
                ],
                'ui_elements' => [
                    'step_navigation' => [
                        'back_button' => [
                            'backgroundColor' => '#d3d3d3',
                            'font' => [
                                'family' => 'Poppins'
                            ],
                        ],
                        'next_button' => [
                            'text' => 'Continue',
                            'backgroundColor' => '#6A1BA2',
                            'font' => [
                                'family' => 'Poppins'
                            ],
                        ],
                        'submit_button' => [
                            'text' => 'Submit',
                            'backgroundColor' => '#6A1BA2',
                            'font' => [
                                'family' => 'Poppins',
                            ],
                            'icon' => 'arrow_right'
                        ]
                    ],
                    'radio_checkbox' => [
                        'checked_color' => '#6A1BA2',
                        'hover_color' => '#6A1BA2',
                        'hover_style' => [
                          'color' => '#ffffff',
                          'border' => [
                            'style' => 'Solid',
                            'color' => '#6A1BA2',
                            'width' => '2'
                          ]
                          ],
                          'active_style' => [
                            'color' => '#ffffff',
                            'border' => [
                              'style' => 'Solid',
                              'color' => '#6A1BA2',
                              'width' => '2'
                            ]
                          ]
                    ],
                    'choice' => [
                        'image_icon_skin' => [
                            'hover_style' => [
                                'border' => [
                                    'color' => '#6A1BA2',
                                ],
                            ],
                            'active_style' => [
                                'border' => [
                                    'color' => '#6A1BA2',
                                ],
                            ],
                        ]
                    ],
                ],
                'custom_css' => '',
            ],
        ],
        [
            'name' => 'Orange theme',
            'config' => [
                'general' => [
                    'colors' => [
                        'active_color' => '#f8bf25'
                    ],
                    'font' => [
                        'family' => 'Raleway'
                    ]
                ],
                'typography' => [
                    'question_title' => [
                        'font' => [
                            'family' => 'Raleway'
                        ]
                    ],
                    'question_description' => [
                        'font' => [
                            'family' => 'Raleway'
                        ]
                    ],
                    'input_box' => [
                        'border' => [
                            'skin' => 'bottom',
                            'style' => 'Solid',
                            'width' => '1',
                            'color' => '#f8bf25',
                        ],
                        'font' => [
                            'family' => 'Raleway',
                            'color' => '#333333',
                            'backgroundColor' => '#E7E7E7'
                        ]
                    ]
                ],
                'ui_elements' => [
                    'background' => [
                        'formShadow' => false,
                        'form_border_width' => '1',
                        'form_border_style' => 'solid',
                        'form_border_color' => '#dddddd',
                        'form_border_radius' => '10',
                    ],
                    'step_navigation' => [
                        'back_button' => [
                            'backgroundColor' => '#d3d3d3',
                            'width' => '70',
                            'alignment' => 'center',
                            'font' => [
                                'family' => 'Raleway'
                            ],
                            'icon' => 'arrow_left'
                        ],
                        'next_button' => [
                            'text' => 'Continue',
                            'backgroundColor' => '#f8bf25',
                            'width' => '70',
                            'alignment' => 'center',
                            'font' => [
                                'family' => 'Raleway',
                                'weight' => '700',
                            ],
                            'icon' => 'arrow_right'
                        ],
                        'submit_button' => [
                            'text' => 'Submit',
                            'backgroundColor' => '#f8bf25',
                            'width' => '70',
                            'alignment' => 'center',
                            'font' => [
                                'family' => 'Raleway',
                            ],
                            'icon' => 'arrow_right'
                        ]
                    ],
                    'step_progress' => [
                      'showProgress' => false
                    ],
                    'radio_checkbox' => [
                        'checked_color' => '#ffffff',
                        'hover_color' => '#ffffff',
                        'hover_style' => [
                            'color' => '#333333',
                            'border' => [
                              'style' => 'Solid',
                              'color' => '#f8bf25',
                              'width' => '2'
                            ]
                            ],
                            'active_style' => [
                                'color' => '#333333',
                                'border' => [
                                  'style' => 'Solid',
                                  'color' => '#f8bf25',
                                  'width' => '2'
                                ]
                            ]
                    ],
                    'choice' => [
                        'image_icon_skin' => [
                            'hover_style' => [
                                'border' => [
                                    'color' => '#f8bf25',
                                ],
                            ],
                            'active_style' => [
                                'border' => [
                                    'color' => '#f8bf25',
                                ],
                            ],
                        ]
                    ],
                ],
                'custom_css' => '',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $themeDefaults = Util::themeDefault();

        foreach ($this->themes as $theme) {
            $config = Util::arrayMergeRecursiveDistinct($themeDefaults, $theme['config']);
            FormThemeTemplate::updateOrCreate(
                [
                    'title' => $theme['name'],
                    'type'  => FormThemeTemplateTypesEnum::DEFAULT,
                ],
                [
                    'title' => $theme['name'],
                    'type' => FormThemeTemplateTypesEnum::DEFAULT,
                    'config' => json_encode($config),
                    'user_id' => null,
                ]
            );
        }
    }
}
