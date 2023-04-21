<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use classes\Elements\Data\ilMDLOMDataFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMStructure implements ilMDStructure
{
    private const NAME_ROOT = 'lom';

    //full structure of LOM
    private const STRUCTURE = [
        self::NAME_ROOT => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'general' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_GENERAL
                ],
                'lifeCycle' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_LIFECYCLE
                ],
                'metaMetadata' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_METAMETADATA
                ],
                'technical' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_TECHNICAL
                ],
                'educational' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_EDUCATIONAL
                ],
                'rights' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_RIGHTS
                ],
                'relation' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_RELATION
                ],
                'annotation' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_ANNOTATION
                ],
                'classification' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_CLASSIFICATION
                ]
            ]
        ]
    ];

    //structures of the main 'container' elements
    private const STRUCTURE_GENERAL = [
        'identifier' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'catalog' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_STRING,
                    'sub' => []
                ],
                'entry' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_STRING,
                    'sub' => []
                ]
            ]
        ],
        'title' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'language' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_LANG,
            'sub' => []
        ],
        'description' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'keyword' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'coverage' => [
            //'unique' => false,
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'structure' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'aggregationLevel' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ]
    ];

    private const STRUCTURE_LIFECYCLE = [
        'version' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'status' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'contribute' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'role' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_VOCAB
                ],
                'entity' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_STRING,
                    'sub' => []
                ],
                'date' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_DATETIME
                ]
            ]
        ]
    ];

    private const STRUCTURE_METAMETADATA = [
        'identifier' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'catalog' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_STRING,
                    'sub' => []
                ],
                'entry' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_STRING,
                    'sub' => []
                ]
            ]
        ],
        'contribute' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'role' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_VOCAB
                ],
                'entity' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_STRING,
                    'sub' => []
                ],
                'date' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_DATETIME
                ]
            ]
        ],
        'metadataSchema' => [
            //'unique' => false,
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_STRING,
            'sub' => []
        ],
        'language' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_LANG,
            'sub' => []
        ]
    ];

    private const STRUCTURE_TECHNICAL = [
        'format' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_STRING,
            'sub' => []
        ],
        'size' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NON_NEG_INT,
            'sub' => []
        ],
        'location' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_STRING,
            'sub' => []
        ],
        'requirement' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'orComposite' => [
                    //'unique' => false,
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => [
                        'type' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_NULL,
                            'sub' => self::STRUCTURE_VOCAB
                        ],
                        'name' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_NULL,
                            'sub' => self::STRUCTURE_VOCAB
                        ],
                        'minimumVersion' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_STRING,
                            'sub' => []
                        ],
                        'maximumVersion' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_STRING,
                            'sub' => []
                        ]
                    ]
                ]
            ]
        ],
        'installationRemarks' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'otherPlatformRequirements' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'duration' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_DURATION
        ]
    ];

    private const STRUCTURE_EDUCATIONAL = [
        'interactivityType' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'learningResourceType' => [
            //'unique' => false,
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'interactivityLevel' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'semanticDensity' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'intendedEndUserRole' => [
            //'unique' => false,
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'context' => [
            //'unique' => false,
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'typicalAgeRange' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'difficulty' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'typicalLearningTime' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_DURATION
        ],
        'description' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'language' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_LANG,
            'sub' => []
        ]
    ];

    private const STRUCTURE_RIGHTS = [
        'cost' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'copyrightAndOtherRestrictions' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'description' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ]
    ];

    private const STRUCTURE_RELATION = [
        'kind' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'resource' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'identifier' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => [
                        'catalog' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_STRING,
                            'sub' => []
                        ],
                        'entry' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_STRING,
                            'sub' => []
                        ]
                    ]
                ],
                'description' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_LANGSTRING
                ]
            ]
        ]
    ];

    private const STRUCTURE_ANNOTATION = [
        'entity' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_STRING,
            'sub' => []
        ],
        'date' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_DATETIME
        ],
        'description' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ]
    ];

    private const STRUCTURE_CLASSIFICATION = [
        'purpose' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_VOCAB
        ],
        'taxonPath' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => [
                'source' => [
                    'unique' => true,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => self::STRUCTURE_LANGSTRING
                ],
                'taxon' => [
                    'unique' => false,
                    'type' => ilMDLOMDataFactory::TYPE_NULL,
                    'sub' => [
                        'id' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_STRING,
                            'sub' => []
                        ],
                        'entry' => [
                            'unique' => true,
                            'type' => ilMDLOMDataFactory::TYPE_NULL,
                            'sub' => self::STRUCTURE_LANGSTRING
                        ]
                    ]
                ]
            ]
        ],
        'description' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ],
        'keyword' => [
            'unique' => false,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ]
    ];

    //common sub-elements
    private const STRUCTURE_LANGSTRING = [
        'string' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_STRING,
            'sub' => []
        ],
        'language' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_LANG,
            'sub' => []
        ]
    ];

    private const STRUCTURE_VOCAB = [
        'source' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_VOCAB_SOURCE,
            'sub' => []
        ],
        'value' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_VOCAB_VALUE,
            'sub' => []
        ]
    ];

    private const STRUCTURE_DATETIME = [
        'dateTime' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_DATETIME,
            'sub' => []
        ],
        'description' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ]
    ];

    private const STRUCTURE_DURATION = [
        'duration' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_DURATION,
            'sub' => []
        ],
        'description' => [
            'unique' => true,
            'type' => ilMDLOMDataFactory::TYPE_NULL,
            'sub' => self::STRUCTURE_LANGSTRING
        ]
    ];

    /**
     * @var string[]
     */
    private array $pointer = [self::NAME_ROOT];
    private array $structure = self::STRUCTURE;
    protected bool $read_mode = false;

    public function isInReadMode(): bool
    {
        return $this->read_mode;
    }

    public function switchToReadMode(): self
    {
        $this->read_mode = true;
        return $this;
    }

    public function getNameAtPointer(): string
    {
        return $this->pointer[array_key_last($this->pointer)];
    }

    public function isPointerAtRootElement(): bool
    {
        return $this->pointer === [self::NAME_ROOT];
    }

    public function isUniqueAtPointer(): bool
    {
        return $this->getSubArrayAtPointer()['unique'];
    }

    /**
     * @return string[]
     */
    public function getSubElementsAtPointer(): array
    {
        return array_keys($this->getSubArrayAtPointer()['sub']);
    }

    public function getTypeAtPointer(): string
    {
        return $this->getSubArrayAtPointer()['type'];
    }

    public function getTagAtPointer(): ?ilMDTag
    {
        return $this->getSubArrayAtPointer()['tag'] ?? null;
    }

    public function setTagAtPointer(ilMDTag $tag): ilMDLOMStructure
    {
        if ($this->read_mode) {
            throw new ilMDStructureException(
                "Can not set tags on a structure in read mode."
            );
        }
        $array = &$this->structure;
        $array = &$array[$this->pointer[0]];
        foreach (array_slice($this->pointer, 1) as $key) {
            $array = &$array['sub'][$key];
        }
        $array['tag'] = $tag;
        return $this;
    }

    public function movePointerToRoot(): self
    {
        $this->pointer = [self::NAME_ROOT];
        return $this;
    }

    public function movePointerToSuperElement(): self
    {
        if ($this->isPointerAtRootElement()) {
            throw new ilMDStructureException(
                "Can not move to a superordinate element from the root."
            );
        }
        unset($this->pointer[array_key_last($this->pointer)]);
        return $this;
    }

    public function movePointerToSubElement(string $name): self
    {
        if (!in_array($name, $this->getSubElementsAtPointer())) {
            throw new ilMDStructureException(
                'The current element ' . $this->getNameAtPointer() .
                ' does not have a subelement with the name ' . $name
            );
        }
        $this->pointer[] = $name;
        return $this;
    }

    private function getSubArrayAtPointer(): array
    {
        $array = $this->structure;
        $array = $array[$this->pointer[0]];
        foreach (array_slice($this->pointer, 1) as $key) {
            $array = $array['sub'][$key];
        }
        return $array;
    }

    public function movePointerToEndOfPath(ilMDPathFromRoot $path): self
    {
        $pointer = [];
        $local_path = clone $path;
        while (!$local_path->isAtStart()) {
            array_unshift($pointer, $local_path->getStep());
            $local_path->removeLastStep();
        }
        array_unshift($pointer, self::NAME_ROOT);
        $this->pointer = $pointer;
        return $this;
    }
}
