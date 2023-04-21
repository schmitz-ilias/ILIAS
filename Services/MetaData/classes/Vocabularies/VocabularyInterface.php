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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface VocabularyInterface
{
    public function source(): string;

    /**
     * @return string[]
     */
    public function values(): array;

    public function isConditional(): bool;

    /**
     * Some vocabularies are only available if a different
     * MD element has a certain value. This value, if there
     * is such a condition, is returned here.
     */
    public function conditionValue(): ?string;
}
