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
class ilMDLOMConstraintDictionary implements ilMDDictionary
{
    public const MD_SCHEMA = 'LOM v 1.0';

    protected ilMDTagFactory $tag_factory;
    protected ilMDLOMConstraintStructure $structure;

    public function __construct(
        ilMDTagFactory $tag_factory
    ) {
        $this->tag_factory = $tag_factory;
        $this->structure = $this->initStructureWithTags();
    }

    /**
     * Returns a LOM structure in read mode, with a editorGUI
     * tag on every element.
     */
    public function getStructure(): ilMDLOMConstraintStructure
    {
        return clone $this->structure;
    }

    protected function initStructureWithTags(): ilMDLOMConstraintStructure
    {
        $structure = new ilMDLOMConstraintStructure();
        $structure->setTagAtPointer(
            $this->getNotDeletableTag(1)
        );
        $this->setTagsForTitleAndIdentifier($structure);
        $this->setTagForMetadataSchema($structure);
        $this->setTagsForDescriptions($structure);
        return $structure->switchToReadMode()
                         ->movePointerToRoot();
    }

    protected function setTagsForTitleAndIdentifier(
        ilMDLOMConstraintStructure $structure
    ): ilMDLOMConstraintStructure {
        $structure
            ->movePointerToSubElement('general')
            ->setTagAtPointer(
                $this->getNotDeletableTag(1)
            )
            ->movePointerToSubElement('title')
            ->setTagAtPointer(
                $this->getNotDeletableTag(1)
            )
            ->movePointerToSubElement('string')
            ->setTagAtPointer(
                $this->getNotDeletableTag(1)
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('identifier')
            ->setTagAtPointer(
                $this->getNotDeletableTag(1)
            )
            ->movePointerToSubElement('catalog')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->addNotDeletable(1)
                    ->addNotEditable(1)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->addNotDeletable(1)
                    ->addNotEditable(1)
                    ->getTag()
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagForMetadataSchema(
        ilMDLOMConstraintStructure $structure
    ): ilMDLOMConstraintStructure {
        $structure
            ->movePointerToSubElement('metaMetadata')
            ->movePointerToSubElement('metadataSchema')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->addNotEditable(1)
                    ->addPresetValue(1, self::MD_SCHEMA)
                    ->getTag()
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForDescriptions(
        ilMDLOMConstraintStructure $structure
    ): ilMDLOMConstraintStructure {
        $structure
            ->movePointerToSubElement('general');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('lifeCycle')
            ->movePointerToSubElement('contribute')
            ->movePointerToSubElement('date');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('metaMetadata')
            ->movePointerToSubElement('contribute')
            ->movePointerToSubElement('date');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('technical')
            ->movePointerToSubElement('duration');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('educational');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToSubElement('typicalLearningTime');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('rights');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('relation')
            ->movePointerToSubElement('resource');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('annotation');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToSubElement('date');
        $this
            ->setDescriptionLongInputTag($structure)
            ->movePointerToRoot()
            ->movePointerToSubElement('classification');
        $this
            ->setDescriptionLongInputTag($structure);

        return $structure->movePointerToRoot();
    }

    protected function setDescriptionLongInputTag(
        ilMDLOMConstraintStructure $structure
    ): ilMDLOMConstraintStructure {
        return $structure
            ->movePointerToSubElement('description')
            ->movePointerToSubElement('string')
            ->setTagAtPointer(
                $this
                    ->getTagBuilder()
                    ->setLongInput(true)
                    ->getTag()
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement();
    }

    protected function getNotDeletableTag(int $index): ilMDConstraintTag
    {
        return $this
            ->getTagBuilder()
            ->addNotDeletable(1)
            ->getTag();
    }

    protected function getTagBuilder(): ilMDConstraintTagBuilder
    {
        return $this->tag_factory->constraint();
    }
}
