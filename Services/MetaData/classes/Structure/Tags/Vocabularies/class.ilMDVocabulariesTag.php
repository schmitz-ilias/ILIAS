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

use classes\Vocabularies\ilMDVocabulary;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDVocabulariesTag extends ilMDTag
{
    /**
     * @var ilMDVocabulary[]
     */
    protected array $vocabularies;
    protected ?ilMDPathRelative $condition_path;

    /**
     * @param ilMDVocabulary[] $vocabularies
     */
    public function __construct(
        array $vocabularies,
        ?ilMDPathRelative $condition_path = null
    ) {
        $this->vocabularies = $vocabularies;
        $this->condition_path = $condition_path;
    }

    /**
     * @return ilMDVocabulary[]
     */
    public function getVocabularies(): array
    {
        return $this->vocabularies;
    }

    /**
     * Some vocabularies are only applicable if a different MD element
     * takes a specific value. If applicable, this returns the path
     * to that different element.
     */
    public function getConditionPath(): ?ilMDPathRelative
    {
        return $this->condition_path;
    }
}
