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

namespace ILIAS\MetaData\Elements\Base;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
abstract class BaseSet implements BaseSetInterface
{
    private BaseElementInterface $root;

    public function __construct(BaseElementInterface $root)
    {
        if (!$root->isRoot() || $root->getSuperElement()) {
            throw new \ilMDElementsException(
                'Metadata sets must be created from a root element.'
            );
        }
        $this->root = $root;
    }

    public function __clone()
    {
        $clone = clone $this;
        $clone->root = clone $this->root;
    }

    public function getRoot(): BaseElementInterface
    {
        return $this->root;
    }
}
