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
class ilMDVocabulariesTagBuilder
{
    /**
     * @var ilMDVocabulary[]
     */
    protected array $vocabularies = [];
    protected ?ilMDPathRelative $condition_path = null;

    /**
     * @param string        $source
     * @param string[]      $values
     * @param string|null   $condition_value
     * @return ilMDVocabulariesTagBuilder
     */
    public function addVocabulary(
        string $source,
        array $values,
        ?string $condition_value = null
    ): ilMDVocabulariesTagBuilder {
        $this->vocabularies[] = new ilMDVocabulary(
            $source,
            $values,
            $condition_value
        );
        return $this;
    }

    /**
     * Some vocabularies are only applicable if a different MD element
     * takes a specific value. Here you can set the path to that different
     * MD element.
     */
    public function setPathToConditionElement(
        ?ilMDPathRelative $path
    ): ilMDVocabulariesTagBuilder {
        $this->condition_path = $path;
        return $this;
    }

    public function getTag(): ilMDVocabulariesTag
    {
        return new ilMDVocabulariesTag(
            $this->vocabularies,
            $this->condition_path
        );
    }
}
