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

use ILIAS\MetaData\Elements\Structure\StructureSetInterface;
use ILIAS\MetaData\Elements\Base\BaseElementInterface;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface Repository
{
    /**
     * Returns a metadata set where every possible element is
     * included once, carrying its definition (name, uniqueness,
     * data type).
     */
    public function getStructure(): StructureSetInterface;

    /**
     * Returns all elements that could be added as sub-elements
     * to the given element as scaffolds. Scaffolds are used to
     * mark where elements could potentially be created.
     * @return ElementInterface[]
     */
    public function getScaffoldsForElement(
        ElementInterface $element
    ): \Generator;
}
