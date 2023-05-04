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

namespace ILIAS\MetaData\Structure;

use ILIAS\MetaData\Elements\Base\BaseElementInterface;
use ILIAS\MetaData\Elements\Structure\StructureFactory;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\Scaffolds\ScaffoldFactory;
use ILIAS\MetaData\Elements\Structure\StructureElement;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactoryInterface;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactory;
use ILIAS\MetaData\Elements\Structure\StructureSetInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class LOMRepository implements Repository
{
    protected StructureFactory $structure_factory;
    protected ScaffoldFactory $scaffold_factory;
    protected PathFactoryInterface $path_factory;
    protected NavigatorFactory $navigator_factory;

    public function __construct(
        StructureFactory $structure_factory,
        ScaffoldFactory $scaffold_factory,
        PathFactoryInterface $path_factory,
        NavigatorFactory $navigator_factory
    ) {
        $this->structure_factory = $structure_factory;
        $this->scaffold_factory = $scaffold_factory;
        $this->path_factory = $path_factory;
        $this->navigator_factory = $navigator_factory;
    }

    public function getStructure(): StructureSetInterface
    {
        return $this->structure_factory->set(
            $this->getStructureRoot()
        );
    }

    protected function getStructureRoot(): StructureElement
    {
        $reader = new LOMDefinitionReader();
        return $this->structure_factory->root(
            $reader->definition(),
            ...$this->getSubElements(...$reader->subDefinitions())
        );
    }

    /**
     * @return LOMDefinitionReader[]
     */
    protected function getSubElements(LOMDefinitionReader ...$readers): \Generator
    {
        foreach ($readers as $reader) {
            yield $this->structure_factory->structure(
                $reader->definition(),
                ...$this->getSubElements(...$reader->subDefinitions())
            );
        }
    }

    /**
     * @return ElementInterface[]
     */
    public function getScaffoldsForElement(
        ElementInterface $element
    ): \Generator {
        $navigator = $this->navigator_factory->structureNavigator(
            $this->path_factory->toElement($element),
            $this->getStructure()->getRoot()
        );
        $structure_element = $navigator->lastElements()[0];

        $sub_names = [];
        foreach ($element->getSubElements() as $sub) {
            $sub_names[] = $sub->getDefinition()->name();
        }

        foreach ($structure_element->getSubElements() as $sub) {
            $unique = $sub->getDefinition()->unqiue();
            $name = $sub->getDefinition()->name();
            if (!$unique || !in_array($name, $sub_names)) {
                yield $this->scaffold_factory->scaffold($sub->getDefinition());
            }
        }
    }
}
