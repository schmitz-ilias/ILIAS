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

namespace ILIAS\MetaData\Elements\Structure;

use ILIAS\MetaData\Elements\Base\BaseSet;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class StructureSet extends BaseSet implements StructureSetInterface
{
    public function __construct(StructureElement $root)
    {
        parent::__construct($root);
    }

    public function getRoot(): StructureElement
    {
        $root = parent::getRoot();
        if ($root instanceof StructureElement) {
            return $root;
        }
        throw new \ilMDElementsException(
            'Metadata set has invalid root element.'
        );
    }
}
