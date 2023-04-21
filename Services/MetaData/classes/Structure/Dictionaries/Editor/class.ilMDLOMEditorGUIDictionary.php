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
    public function getStructure(): ilMDLOMEditorGUIStructure
    {
        return clone $this->structure;
    }

    protected function initStructureWithTags(): ilMDLOMEditorGUIStructure
    {
        $structure = new ilMDLOMEditorGUIStructure();
        $structure->setTagAtPointer($this->getTagBuilder()->getTag());
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
            ->setTagAtPointer($this->getTagBuilder()->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'identifier',
            ['entry'],
            true
        );
        $structure
            ->movePointerToSubElement('identifier')
            ->movePointerToSubElement('catalog')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement();
        $this->setTagForNoRepSubElement(
            $structure,
            'title',
            ['string']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'language',
            [],
            true,
            true,
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
            //true
            false
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
            ->setTagAtPointer($this->getTagBuilder()->getTag());
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
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath('contribute', ['entity'])
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath('contribute', ['role', 'value'])
                    )
                    ->setPathToForward(
                        $this->getRelativePath('contribute', ['role'])
                    )
                    ->getTag()
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'role',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'entity',
            [],
            true,
            true,
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
            ->setTagAtPointer($this->getTagBuilder()->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'identifier',
            ['entry'],
            true
        );
        $structure
            ->movePointerToSubElement('identifier')
            ->movePointerToSubElement('catalog')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('contribute')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath('contribute', ['entity'])
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath('contribute', ['role', 'value'])
                    )
                    ->setPathToForward(
                        $this->getRelativePath('contribute', ['role'])
                    )
                    ->getTag()
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'role',
            ['value']
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'entity',
            [],
            true,
            true,
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
            //true
            false,
            true,
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'language',
            [],
            false,
            true,
            true
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForTechnical(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('technical')
            ->setTagAtPointer($this->getTagBuilder()->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'format',
            [],
            true,
            true,
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'size',
            [],
            false,
            true,
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'location',
            [],
            true,
            true,
            true
        );
        $structure
            ->movePointerToSubElement('requirement')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'requirement',
                            ['orComposite', 'name', 'value']
                        )
                    )
                    ->setPathToForward(
                        $this->getRelativePath(
                            'requirement',
                            ['orComposite']
                        )
                    )
                    ->getTag()
            )
            ->movePointerToSubElement('orComposite')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'orComposite',
                            ['name', 'value']
                        )
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath(
                            'orComposite',
                            ['type', 'value']
                        )
                    )
                    /*->setCollectionMode(
                        ilMDLOMEditorGUIDictionary::COLLECTION_TABLE
                    )*/
                    ->getTag()
            )
            ->movePointerToSubElement('minimumVersion')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('maximumVersion')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
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
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'educational',
                            ['typicalLearningTime', 'duration']
                        )
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath(
                            'educational',
                            ['interactivityType', 'value']
                        )
                    )
                    ->setCollectionMode(
                        ilMDLOMEditorGUIDictionary::COLLECTION_NODE
                    )
                    ->getTag()
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
            //true
            false
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
            //true
            false
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'context',
            ['value'],
            //true
            false
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
            true,
            true,
            true
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRights(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('rights')
            ->setTagAtPointer($this->getTagBuilder()->getTag());
        $this->setTagForNoRepSubElement(
            $structure,
            'cost',
            ['value'],
            false,
            false
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'copyrightAndOtherRestrictions',
            ['value'],
            false,
            false
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'description',
            ['string'],
            false,
            false
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRelation(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('relation')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'relation',
                            ['resource', 'identifier', 'entry']
                        )
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath('relation', ['kind', 'value'])
                    )
                    ->setPathToForward(
                        $this->getRelativePath('relation', ['kind'])
                    )
                    ->setCollectionMode(
                        ilMDLOMEditorGUIDictionary::COLLECTION_NODE
                    )
                    ->getTag()
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'kind',
            ['value']
        );
        $structure
            ->movePointerToSubElement('resource')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'resource',
                            ['identifier', 'entry']
                        )
                    )
                    ->setPathToForward(
                        $this->getRelativePath(
                            'resource',
                            ['identifier']
                        )
                    )
                    ->getTag()
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'identifier',
            ['entry'],
            true
        );
        $structure
            ->movePointerToSubElement('identifier')
            ->movePointerToSubElement('catalog')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLabelImportant(true)
                    ->setInTree(false)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement();
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
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'annotation',
                            ['description', 'string']
                        )
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath('annotation', ['entity'])
                    )
                    ->setCollectionMode(
                        ilMDLOMEditorGUIDictionary::COLLECTION_TABLE
                    )
                    ->getTag()
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'entity',
            [],
            false,
            false,
            true
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'date',
            ['dateTime'],
            false,
            false
        );
        $this->setTagForNoRepSubElement(
            $structure,
            'description',
            ['string'],
            false,
            false
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForClassification(
        ilMDLOMEditorGUIStructure $structure
    ): ilMDLOMEditorGUIStructure {
        $structure
            ->movePointerToSubElement('classification')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'classification',
                            ['taxonPath', 'taxon', 'entry', 'string']
                        )
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath(
                            'classification',
                            ['purpose', 'value']
                        )
                    )
                    ->setPathToForward(
                        $this->getRelativePath(
                            'classification',
                            ['purpose']
                        )
                    )
                    ->setCollectionMode(
                        ilMDLOMEditorGUIDictionary::COLLECTION_NODE
                    )
                    ->getTag()
            );
        $this->setTagForNoRepSubElement(
            $structure,
            'purpose',
            ['value']
        );
        $structure
            ->movePointerToSubElement('taxonPath')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath(
                            'taxonPath',
                            ['taxon', 'entry', 'string']
                        )
                    )
                    ->setPathToRepresentation(
                        $this->getRelativePath('taxonPath', ['source', 'string'])
                    )
                    ->setPathToForward(
                        $this->getRelativePath('taxonPath', ['source'])
                    )
                    ->getTag()
            );
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
     * @param bool                      $in_tree
     * @param bool                      $label_important
     * @return ilMDLOMEditorGUIStructure
     */
    protected function setTagForNoRepSubElement(
        ilMDLOMEditorGUIStructure $structure,
        string $name,
        array $steps_to_preview,
        bool $collected_as_table = false,
        bool $in_tree = true,
        bool $label_important = false
    ): ilMDLOMEditorGUIStructure {
        return $structure
            ->movePointerToSubElement($name)
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setPathToPreview(
                        $this->getRelativePath($name, $steps_to_preview)
                    )
                    ->setCollectionMode(
                        $collected_as_table ?
                            ilMDLOMEditorGUIDictionary::COLLECTION_TABLE :
                            ilMDLOMEditorGUIDictionary::NO_COLLECTION
                    )
                    ->setInTree($in_tree)
                    ->setLabelImportant($label_important)
                    ->getTag()
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

    protected function getTagBuilder(): ilMDEditorGUITagBuilder
    {
        return $this->tag_factory->editor();
    }
}
