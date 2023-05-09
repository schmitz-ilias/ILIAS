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

namespace ILIAS\MetaData\Structure\Dictionaries;

use ILIAS\MetaData\Structure\Dictionaries\Tags\TagInterface;
use ILIAS\MetaData\Elements\Structure\StructureElementInterface;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactoryInterface;
use ILIAS\MetaData\Structure\Dictionaries\Tags\TagAssignmentInterface;
use ILIAS\MetaData\Structure\Dictionaries\Tags\TagAssignment;
use ILIAS\MetaData\Elements\Structure\StructureSetInterface;
use ILIAS\MetaData\Structure\RepositoryInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
abstract class DictionaryInitiator implements DictionaryInitiatorInterface
{
    protected PathFactoryInterface $path_factory;
    private StructureSetInterface $structure_set;

    /**
     * @var TagAssignmentInterface[]
     */
    private array $tag_assignments = [];

    public function __construct(
        PathFactoryInterface $path_factory,
        RepositoryInterface $repository
    ) {
        $this->path_factory = $path_factory;
        $this->structure_set = $repository->getStructure();
    }

    /**
     * When indices are added, the tag applies only
     * to copies of the element with those indices
     * (beginning with 0).
     */
    final protected function addTagToElement(
        TagInterface $tag,
        StructureElementInterface $element,
        int ...$indices
    ): void {
        $this->tag_assignments[] = new TagAssignment(
            $this->path_factory->toElement($element),
            $tag
        );
    }

    final protected function getStructureSet(): StructureSetInterface
    {
        return $this->structure_set;
    }

    /**
     * Use this method to set up the tags for the dictionary.
     */
    abstract protected function initDictionary(): void;

    final public function get(): DictionaryInterface
    {
        $this->initDictionary();
        return new Dictionary($this->path_factory, ...$this->tag_assignments);
    }
}
