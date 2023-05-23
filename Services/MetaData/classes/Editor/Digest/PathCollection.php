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

namespace ILIAS\MetaData\Editor\Digest;

use ILIAS\MetaData\Paths\FactoryInterface as PathFactory;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Elements\Structure\StructureSetInterface;
use ILIAS\MetaData\Paths\Filters\FilterType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class PathCollection
{
    protected PathInterface $title;
    protected PathInterface $descriptions;
    protected PathInterface $languages;
    protected PathInterface $keywords;
    protected PathInterface $first_three_authors;
    protected PathInterface $first_typical_learning_time;
    protected PathInterface $copyright;
    protected PathInterface $has_copyright;

    public function __construct(
        PathFactory $path_factory,
        StructureSetInterface $structure
    ) {
        $this->init($path_factory, $structure);
    }

    protected function init(
        PathFactory $path_factory,
        StructureSetInterface $structure
    ): void {
        $general = $structure->getRoot()->getSubElement('general');
        $this->title = $path_factory->toElement(
            $general->getSubElement('title')->getSubElement('string')
        );
        $this->descriptions = $path_factory->toElement(
            $general->getSubElement('description')->getSubElement('string')
        );
        $this->languages = $path_factory->toElement(
            $general->getSubElement('language')
        );
        $this->keywords = $path_factory->toElement(
            $general->getSubElement('keyword')->getSubElement('string')
        );

        $lifecycle = $structure->getRoot()->getSubElement('lifeCycle');
        $contribute = $lifecycle->getSubElement('contribute');
        $role = $contribute->getSubElement('role');
        $entity = $contribute->getSubElement('entity');
        $this->first_three_authors = $path_factory
            ->custom()
            ->withNextStep($lifecycle->getDefinition())
            ->withNextStep($contribute->getDefinition())
            ->withNextStep($role->getDefinition())
            ->withAdditionalFilterAtCurrentStep(FilterType::DATA, 'author')
            ->withNextStepToSuperElement()
            ->withNextStep($entity->getDefinition())
            ->withAdditionalFilterAtCurrentStep(FilterType::INDEX, '1', '2', '3')
            ->get();

        $educational = $structure->getRoot()->getSubElement('educational');
        $tlt = $structure->getRoot()->getSubElement('typicalLearningTime');
        $duration = $structure->getRoot()->getSubElement('duration');
        $this->first_typical_learning_time = $path_factory
            ->custom()
            ->withNextStep($educational->getDefinition())
            ->withAdditionalFilterAtCurrentStep(FilterType::INDEX, '1')
            ->withNextStep($tlt->getDefinition())
            ->withNextStep($duration->getDefinition())
            ->get();

        $rights = $structure->getRoot()->getSubElement('rights');
        $this->descriptions = $path_factory->toElement(
            $rights->getSubElement('description')->getSubElement('string')
        );
        $this->title = $path_factory->toElement(
            $rights->getSubElement('copyrightAndOtherRestrictions')->getSubElement('value')
        );
    }

    public function title(): PathInterface
    {
        return $this->title;
    }

    public function descriptions(): PathInterface
    {
        return $this->descriptions;
    }

    public function languages(): PathInterface
    {
        return $this->languages;
    }

    public function keywords(): PathInterface
    {
        return $this->keywords;
    }

    public function firstThreeAuthors(): PathInterface
    {
        return $this->first_three_authors;
    }

    public function firstTypicalLearningTime(): PathInterface
    {
        return $this->first_typical_learning_time;
    }

    public function copyright(): PathInterface
    {
        return $this->copyright;
    }

    public function hasCopyright(): PathInterface
    {
        return $this->has_copyright;
    }
}
