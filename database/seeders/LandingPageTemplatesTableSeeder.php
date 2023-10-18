<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Enums\LandingPageTemplateCodesEnum as TemplateEnum;
use App\LandingPageTemplate;

class LandingPageTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tpl1 = LandingPageTemplate::where(
            'code',
            TemplateEnum::TPL1
        )->first();
        if (empty($tpl1)) {
            $this->createTPL1();
        } else {
            $tpl1->config = json_encode($this->getConfig());
            $tpl1->save();
        }
    }

    public function createTPL1()
    {
        LandingPageTemplate::create([
            'title' => 'Text and Media',
            'code' => TemplateEnum::TPL1,
            'config' => json_encode($this->getConfig()),
        ]);
    }

    public function getConfig()
    {
        return [
            'title' => '<h1>The Title<h1>',
            'description' => '<p>The Description</p>',
            'visibility' => [
                'show_description' => [
                    'title' => 'Description',
                    'value' => true,
                ],
                'show_headline' => [
                    'title' => 'Headline',
                    'value' => true,
                ],
                'show_cta1' => [
                    'title' => 'Button',
                    'value' => true,
                ],
                'show_media' => [
                    'title' => 'Media',
                    'value' => true
                ]
            ],
            'colors' => [
                'body_bg' => [
                    'title' => 'Background',
                    'value' => '#ffffff',
                ],
                'cta1_bg' => [
                    'title' => 'Button Background',
                    'value' => '#3292ff',
                ],
                'cta1_color' => [
                    'title' => 'Button Text',
                    'value' => '#000000',
                ]
            ],
            'media_type' => [
                'meta_readonly' => [
                    'types' => ['image', 'video'],
                    'sources' => ['upload', 'url'],
                    'image_ext' => ['jpg', 'png', 'jpeg', 'gif'],
                    'video_ext' => ['mp4', 'webm', 'ogg'],
                    'image_size' => '5',
                    'video_size' => '5',
                    'size_unit' => 'MB',
                    'positions' => [
                        [
                            'title' => 'Above Headline',
                            'value' => 'above_headline',
                        ],
                        [
                            'title' => 'Above Description',
                            'value' => 'above_description',
                        ],
                        [
                            'title' => 'Content Left Side',
                            'value' => 'content_left_side',
                        ],
                        [
                            'title' => 'Content Right Side',
                            'value' => 'content_right_side',
                        ],
                    ]
                ],
                'type' => 'image',
                'source' => 'upload',
                'video_url' => '',
                'image_url' => '',
                'is_youtube_video' => false,
                'position' => 'above_headline'
            ],
            'cta' => [
                'meta_readonly' => [
                    'actions' => [
                        [
                            'title' => 'Go To URL',
                            'value' => 'go_to_url'
                        ],
                        [
                            'title' => 'Show leadgen form',
                            'value' => 'show_leadgen_form'
                        ]
                    ],
                    'sizes' => [
                        [
                            'title' => 'Small',
                            'value' => 'small'
                        ],
                        [
                            'title' => 'Normal',
                            'value' => 'normal'
                        ],
                        [
                            'title' => 'Large',
                            'value' => 'large'
                        ]
                    ]
                ],
                'url' => 'http://example.com',
                'leadgen_form_id' => '',
                'cta_text' => 'Show Me How',
                'cta_size' => 'small',
                'cta_fullwidth' => false
            ],
            'tracking' => [
                'enable' => false,
                'meta_readonly' => [
                    'script_types' => [
                        [
                            'title' => 'Script Tag',
                            'value' => 'script_tag'
                        ],
                        [
                            'title' => 'Script URL',
                            'value' => 'script_url'
                        ],
                        [
                            'title' => 'No Script Tag',
                            'value' => 'noscript'
                        ]
                    ],
                    'positions' => [
                        [
                            'title' => 'After Opening Head Tag',
                            'value' => 'after_head_opening'
                        ],
                        [
                            'title' => 'Before Closing Head Tag',
                            'value' => 'before_head_closing',
                        ],
                        [
                            'title' => 'After Opening Body Tag',
                            'value' => 'after_body_opening'
                        ],
                        [
                            'title' => 'Before Closing Body Tag',
                            'value' => 'before_body_closing'
                        ]
                    ],
                    'sample_script' => [
                        'tag' => 'script_tag',
                        'position' => 'after_body_opening',
                        'url' => 'http://script.com/index.js',
                        'content' => 'alert("Hello Leadgen!")',
                        'order' => 1,
                        'async' => false
                    ]
                ],
                'scripts' => [
                ]
            ]
        ];
    }
}
