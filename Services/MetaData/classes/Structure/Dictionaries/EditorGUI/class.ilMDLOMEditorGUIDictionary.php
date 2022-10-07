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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMEditorGUIDictionary implements ilMDDictionary
{
    public const NO_COLLECTION = 'md_not_collected';
    public const COLLECTION_NODE = 'md_collected_node';
    public const COLLECTION_TABLE = 'md_collected_table';

    protected ilMDTagFactory $tag_factory;
    protected ilMDPathFactory $path_factory;

    protected ilMDLOMEditorGUIStructure $structure;

    public function __construct(
        ilMDTagFactory $tag_factory,
        ilMDPathFactory $path_factory
    ) {
        $this->tag_factory = $tag_factory;
        $this->path_factory = $path_factory;
        $this->structure = $this->initStructureWithTags();
    }

    /**
     * Returns a LOM structure in read mode, with a editorGUI
     * tag on every element.
     */
    public function getStructureWithTags(): ilMDLOMEditorGUIStructure
    {
        return clone $this->structure;
    }

    protected function initStructureWithTags(): ilMDLOMEditorGUIStructure
    {
        $structure = new ilMDLOMEditorGUIStructure();
        $structure->setTagAtPointer($this->getTag());
        $this->setTagsForGeneral($structure);
        $this->setTagsForLifecycle($structure);
        $this->setTagsForMetaMetadata($structure);
        $this->setTagsForTechnical($structure);
        $this->setTagsForEducational($structure);
        $this->setTagsForRights($structure);
        $this->setTagsForRelation($structure);
        $this->setTagsForAnnotation($structure);
        $this->setTagsForClassification($structure);
        return $structure->switchToReadMode()
                         ->movePointerToRoot();
    }

    protected function setTagsForGeneral(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('general')
            ->setTagAtPointer($this->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'identifier',
            ['entry'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'title',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'language',
            [],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'description',
            ['string'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'keyword',
            ['string'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'coverage',
            ['string'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'structure',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'aggregationLevel',
            ['value']
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForLifecycle(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('lifeCycle')
            ->setTagAtPointer($this->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'version',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'status',
            ['value']
        );
        $structure
            ->movePointerToSubElement('contribute')
            ->setTagAtPointer($this->getTag(
                $this->getRelativePath('contribute', ['entity']),
                $this->getRelativePath('contribute', ['role', 'value'])
            ));
        $this->setTagForNoRepSubElement(
            $structure,
            'role',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'entity',
            [],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'date',
            ['dateTime']
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForMetaMetadata(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('metaMetadata')
            ->setTagAtPointer($this->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'identifier',
            ['entry'],
            true
        );
        $structure
            ->movePointerToSubElement('contribute')
            ->setTagAtPointer($this->getTag(
                $this->getRelativePath('contribute', ['entity']),
                $this->getRelativePath('contribute', ['role', 'value'])
            ));
        $this->setTagForNoRepSubElement(
            $structure,
            'role',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'entity',
            [],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'date',
            ['dateTime']
        );
        $structure->movePointerToSuperElement();
        $this->setTagForNoRepSubElement(
            $structure,
            'metadataSchema',
            [],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'language',
            []
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForTechnical(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('technical')
            ->setTagAtPointer($this->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'format',
            [],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'size',
            []
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'location',
            [],
            true
        );
        $structure
            ->movePointerToSubElement('requirement')
            ->setTagAtPointer($this->getTag(
                $this->getRelativePath(
                    'requirement',
                    ['orComposite', 'name', 'value']
                )
            ))
            ->movePointerToSubElement('orComposite')
            ->setTagAtPointer(
                $this->getTag(
                    $this->getRelativePath('orComposite', ['name', 'value']),
                    $this->getRelativePath('orComposite', ['type', 'value'])
                )
                ->withCollectionMode(
                    ilMDLOMEditorGUIDictionary::COLLECTION_TABLE
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement();
        $this->setTagForNoRepSubElement(
            $structure,
            'installationRemarks',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'otherPlatformRequirements',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'duration',
            ['duration']
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForEducational(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('educational')
            ->setTagAtPointer(
                $this->getTag(
                    $this->getRelativePath(
                        'educational',
                        ['typicalLearningTime', 'duration']
                    ),
                    $this->getRelativePath(
                        'educational',
                        ['interactivityType', 'value']
                    )
                )->withCollectionMode(
                    ilMDLOMEditorGUIDictionary::COLLECTION_NODE
                )
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'interactivityType',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'learningResourceType',
            ['value'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'interactivityLevel',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'semanticDensity',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'intendedEndUserRole',
            ['value'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'context',
            ['value'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'typicalAgeRange',
            ['string'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'difficulty',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'typicalLearningTime',
            ['duration']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'description',
            ['string'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'language',
            [],
            true
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRights(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('rights')
            ->setTagAtPointer($this->getTag());
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRelation(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('relation')
            ->setTagAtPointer(
                $this->getTag(
                    $this->getRelativePath(
                        'relation',
                        ['resource', 'identifier', 'entry']
                    ),
                    $this->getRelativePath('relation', ['kind', 'value'])
                )->withCollectionMode(
                    ilMDLOMEditorGUIDictionary::COLLECTION_NODE
                )
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'kind',
            ['value']
        );
        $structure
            ->movePointerToSubElement('resource')
            ->setTagAtPointer($this->getTag(
                $this->getRelativePath('resource', ['identifier', 'entry'])
            ));
        $this->setTagForNoRepSubElement(
            $structure,
            'identifier',
            ['entry'],
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'description',
            ['string'],
            true
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForAnnotation(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('annotation')
            ->setTagAtPointer(
                $this->getTag()->withCollectionMode(
                    ilMDLOMEditorGUIDictionary::COLLECTION_TABLE
                )
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForClassification(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('classification')
            ->setTagAtPointer(
                $this->getTag(
                    $this->getRelativePath(
                        'classification',
                        ['taxonPath', 'taxon', 'entry', 'string']
                    ),
                    $this->getRelativePath('classification', ['purpose', 'value'])
                )->withCollectionMode(
                    ilMDLOMEditorGUIDictionary::COLLECTION_NODE
                )
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'purpose',
            ['value']
        );
        $structure
            ->movePointerToSubElement('taxonPath')
            ->setTagAtPointer($this->getTag(
                $this->getRelativePath('taxonPath', ['source', 'string']),
                $this->getRelativePath(
                    'taxonPath',
                    ['taxon', 'entry', 'string']
                )
            ));
        $this->setTagForNoRepSubElement(
            $structure,
            'source',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'taxon',
            ['entry', 'string'],
            true
        );
        $structure->movePointerToSuperElement();
        $this->setTagForNoRepSubElement(
            $structure,
            'description',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'keyword',
            ['string'],
            true
        );
        return $structure->movePointerToRoot();
    }

    /**
     * @param ilMDLOMEditorGUIStructure $structure
     * @param string                    $name
     * @param string[]                  $steps_to_preview
     * @param bool                      $collected_as_table
     * @return ilMDLOMEditorGUIStructure
     */
    protected function setTagForNoRepSubElement(
        ilMDLOMEditorGUIStructure $structure,
        string $name,
        array $steps_to_preview,
        bool $collected_as_table = false
    ): ilMDLOMEditorGUIStructure {
        return $structure
            ->movePointerToSubElement($name)
            ->setTagAtPointer(
                $this->getTag(
                    $this->getRelativePath($name, $steps_to_preview)
                )
                ->withCollectionMode(
                    $collected_as_table ?
                        ilMDLOMEditorGUIDictionary::COLLECTION_TABLE :
                        ilMDLOMEditorGUIDictionary::NO_COLLECTION
                )
            )
            ->movePointerToSuperElement();
    }

    /**
     * @param string   $start
     * @param string[] $steps
     * @return ilMDPathRelative
     */
    protected function getRelativePath(
        string $start,
        array $steps
    ): ilMDPathRelative {
        $path =  $this->path_factory->getRelativePath($start);
        foreach ($steps as $step) {
            $path->addStep($step);
        }
        return $path;
    }

    protected function getTag(
        ?ilMDPathRelative $path_to_preview = null,
        ?ilMDPathRelative $path_to_representation = null
    ): ilMDEditorGUITag {
        return $this->tag_factory
            ->editorGUITag(
                $path_to_preview,
                $path_to_representation
            );
    }
}
