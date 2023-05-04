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

namespace ILIAS\MetaData\Paths\Navigator;

use ILIAS\MetaData\Elements\Structure\StructureElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface StructureNavigatorInterface extends BaseNavigatorInterface
{
    public function nextStep(): ?NavigatorInterface;

    /**
     * Returns the elements at the end of the path.
     * This always returns exactly one element or
     * throws an error.
     * @return StructureElementInterface[]
     * @throws \ilMDPathException
     */
    public function lastElements(): \Generator;

    /**
     * Returns the elements at the current step in the path.
     * This always returns exactly one element or
     * throws an error.
     * @return StructureElementInterface[]
     * @throws \ilMDPathException
     */
    public function elements(): \Generator;
}
