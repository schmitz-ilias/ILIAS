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
class ilMDLOMVocabulariesDictionary implements ilMDDictionary
{
    public const SOURCE = 'LOMv1.0';
    protected ilMDTagFactory $tag_factory;
    protected ilMDPathFactory $path_factory;

    protected ilMDLOMVocabulariesStructure $structure;

    public function __construct(
        ilMDTagFactory $tag_factory,
        ilMDPathFactory $path_factory
    ) {
        $this->tag_factory = $tag_factory;
        $this->path_factory = $path_factory;
        $this->structure = $this->initStructureWithTags();
    }

    /**
     * Returns a LOM structure in read mode, with a vocabulary
     * tag on every vocabulary value or source.
     */
    public function getStructure(): ilMDLOMVocabulariesStructure
    {
        return clone $this->structure;
    }

    protected function initStructureWithTags(): ilMDLOMVocabulariesStructure
    {
        $structure = new ilMDLOMVocabulariesStructure();
        $this->setTagsForGeneral($structure);
        $this->setTagsForLifecycle($structure);
        $this->setTagsForMetaMetadata($structure);
        $this->setTagsForTechnical($structure);
        $this->setTagsForEducational($structure);
        $this->setTagsForRights($structure);
        $this->setTagsForRelation($structure);
        $this->setTagsForClassification($structure);
        return $structure->switchToReadMode()
                         ->movePointerToRoot();
    }

    protected function setTagsForGeneral(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('general')
            ->movePointerToSubElement('structure');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['atomic', 'collection', 'networked', 'hierarchical', 'linear']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('aggregationLevel');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['1', '2', '3', '4']
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForLifecycle(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('lifeCycle')
            ->movePointerToSubElement('status');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['draft', 'final', 'revised', 'unavailable']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('contribute')
            ->movePointerToSubElement('role');
        $this
            ->setTagsForVocabulary(
                $structure,
                [
                    'author', 'publisher', 'unknown', 'initiator',
                    'terminator', 'editor', 'graphical designer',
                    'technical implementer', 'content provider',
                    'technical validator', 'educational validator',
                    'script writer', 'instructional designer',
                    'subject matter expert'
                ]
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForMetaMetadata(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('metaMetadata')
            ->movePointerToSubElement('contribute')
            ->movePointerToSubElement('role');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['creator', 'validator']
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForTechnical(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('technical')
            ->movePointerToSubElement('requirement')
            ->movePointerToSubElement('orComposite')
            ->movePointerToSubElement('type');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['operating system', 'browser']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('name');
        $this
            ->setTagsForVocabulary(
                $structure,
                [
                    //this is the vocab for type = os
                    'pc-dos', 'ms-windows', 'macos', 'unix', 'multi-os',
                    'none',
                    //this is the vocab for type = browser
                    'any', 'netscape communicator', 'ms-internet explorer',
                    'opera', 'amaya'
                ]
            )
            ->movePointerToSubElement('value')
            ->setTagAtPointer(
                $this->tag_factory
                    ->vocabularies()
                    ->addVocabulary(
                        self::SOURCE,
                        [
                            'pc-dos', 'ms-windows', 'macos', 'unix',
                            'multi-os', 'none'
                        ],
                        'operating system'
                    )
                    ->addVocabulary(
                        self::SOURCE,
                        [
                            'any', 'netscape communicator',
                            'ms-internet explorer', 'opera', 'amaya'
                        ],
                        'browser'
                    )
                    ->setPathToConditionElement(
                        $this->path_factory
                            ->getRelativePath('value')
                            ->addStepToSuperElement()
                            ->addStepToSuperElement()
                            ->addStep('type')
                            ->addStep('value')
                    )
                    ->getTag()
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForEducational(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('educational')
            ->movePointerToSubElement('interactivityType');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['active', 'expositive', 'mixed']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('learningResourceType');
        $this
            ->setTagsForVocabulary(
                $structure,
                [
                    'exercise', 'simulation', 'questionnaire', 'diagram',
                    'figure', 'graph', 'index', 'slide', 'table',
                    'narrative text', 'exam', 'experiment',
                    'problem statement', 'self assessment', 'lecture'
                ]
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('interactivityLevel');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['very low', 'low', 'medium', 'high', 'very high']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('semanticDensity');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['very low', 'low', 'medium', 'high', 'very high']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('intendedEndUserRole');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['teacher', 'author', 'learner', 'manager']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('context');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['school', 'higher education', 'training', 'other']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('difficulty');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['very easy', 'easy', 'medium', 'difficult', 'very difficult']
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRights(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('rights')
            ->movePointerToSubElement('cost');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['yes', 'no']
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('copyrightAndOtherRestrictions');
        $this
            ->setTagsForVocabulary(
                $structure,
                ['yes', 'no']
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRelation(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('relation')
            ->movePointerToSubElement('kind');
        $this
            ->setTagsForVocabulary(
                $structure,
                [
                    'ispartof', 'haspart', 'isversionof', 'hasversion',
                    'isformatof', 'hasformat', 'references', 'isreferencedby',
                    'isbasedon', 'isbasisfor', 'requires', 'isrequiredby'
                ]
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForClassification(
        ilMDLOMVocabulariesStructure $structure
    ): ilMDLOMVocabulariesStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('classification')
            ->movePointerToSubElement('purpose');
        $this
            ->setTagsForVocabulary(
                $structure,
                [
                    'discipline', 'idea', 'prerequisite',
                    'educational objective', 'accessibility restrictions',
                    'educational level', 'skill level', 'security level',
                    'competency'
                ]
            );
        return $structure->movePointerToRoot();
    }

    /**
     * @param ilMDLOMVocabulariesStructure $structure
     * @param string[]                     $values
     * @return ilMDLOMVocabulariesStructure
     */
    protected function setTagsForVocabulary(
        ilMDLOMVocabulariesStructure $structure,
        array $values
    ): ilMDLOMVocabulariesStructure {
        $tag = $this->getTag($values);
        $structure
            ->movePointerToSubElement('source')
            ->setTagAtPointer($tag)
            ->movePointerToSuperElement()
            ->movePointerToSubElement('value')
            ->setTagAtPointer($tag)
            ->movePointerToSuperElement();
        return $structure;
    }

    /**
     * @param string[] $values
     * @return ilMDVocabulariesTag
     */
    protected function getTag(array $values): ilMDVocabulariesTag
    {
        return $this->tag_factory
            ->vocabularies()
            ->addVocabulary(self::SOURCE, $values)
            ->getTag();
    }
}
