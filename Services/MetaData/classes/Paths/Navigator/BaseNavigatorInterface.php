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

use ILIAS\MetaData\Elements\Base\BaseElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface BaseNavigatorInterface
{
    /**
     * Returns null if there is no next step.
     */
    public function nextStep(): ?BaseNavigatorInterface;

    /**
     * @return BaseElementInterface[]
     * @throws \ilMDPathException
     */
    public function lastElements(): \Generator;

    /**
     * @return BaseElementInterface[]
     * @throws \ilMDPathException
     */
    public function elements(): \Generator;
}
