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

namespace ILIAS\MetaData\Vocabularies;

use ILIAS\MetaData\Elements\Base\BaseElementInterface;
use ILIAS\MetaData\Elements\ElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface VocabulariesInterface
{
    /**
     * @return VocabularyInterface[]
     */
    public function vocabulariesForElement(
        BaseElementInterface $element
    ): \Generator;

    /**
     * This only returns vocabularies which are not conditional or whose
     * condition is fulfilled. For vocabulary value elements, the corresponding
     * source element is also checked, and vocabularies filtered accordingly.
     * When available, data from markers is preferred, unless ignore_marker
     * is set true.
     * @return VocabularyInterface[]
     */
    public function filteredVocabulariesForElement(
        ElementInterface $element,
        bool $ignore_marker
    ): \Generator;
}
