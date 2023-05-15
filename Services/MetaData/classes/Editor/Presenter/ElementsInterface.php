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

namespace ILIAS\MetaData\Editor\Presenter;

use ILIAS\MetaData\Elements\Base\BaseElementInterface;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface ElementsInterface
{
    public function nameWithRepresentation(
        ElementInterface $element,
        bool $force_singular = false
    ): string;

    public function preview(ElementInterface $element): string;

    public function name(
        BaseElementInterface $element,
        bool $plural = false
    ): string;

    public function nameWithParents(
        BaseElementInterface $element,
        ?BaseElementInterface $cut_off = null,
        bool $plural = false,
        bool $never_skip_initial = false
    ): string;
}
