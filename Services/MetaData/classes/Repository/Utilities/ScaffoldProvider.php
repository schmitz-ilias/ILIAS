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

namespace ILIAS\MetaData\Repository\Utilities;

use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactoryInterface;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactoryInterface;
use ILIAS\MetaData\Elements\Structure\StructureSetInterface;
use ILIAS\MetaData\Elements\Scaffolds\ScaffoldFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ScaffoldProvider implements ScaffoldProviderInterface
{
    protected ScaffoldFactory $scaffold_factory;
    protected PathFactoryInterface $path_factory;
    protected NavigatorFactoryInterface $navigator_factory;
    protected StructureSetInterface $structure;

    public function __construct(
        ScaffoldFactory $scaffold_factory,
        PathFactoryInterface $path_factory,
        NavigatorFactoryInterface $navigator_factory,
        StructureSetInterface $structure,
    ) {
        $this->scaffold_factory = $scaffold_factory;
        $this->path_factory = $path_factory;
        $this->navigator_factory = $navigator_factory;
        $this->structure = $structure;
    }

    /**
     * @return ElementInterface[]
     */
    public function getScaffoldsForElement(
        ElementInterface $element
    ): \Generator {
        $navigator = $this->navigator_factory->structureNavigator(
            $this->path_factory->toElement($element),
            $this->structure->getRoot()
        );
        $structure_element = $navigator->elementAtLastStep();

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
