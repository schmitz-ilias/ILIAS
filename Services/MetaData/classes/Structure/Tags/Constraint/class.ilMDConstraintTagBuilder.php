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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDConstraintTagBuilder
{
    protected bool $long_input = false;

    /**
     * Keys are indices of affected elements.
     * @var string[]
     */
    protected array $preset_values = [];

    /**
     * @var int[]
     */
    protected array $indices_not_deletable = [];

    /**
     * @var int[]
     */
    protected array $indices_not_editable = [];

    public function setLongInput(
        bool $long_input
    ): ilMDConstraintTagBuilder {
        $this->long_input = $long_input;
        return $this;
    }

    public function addPresetValue(
        int $index,
        string $value
    ): ilMDConstraintTagBuilder {
        $this->preset_values[$index] = $value;
        return $this;
    }

    public function addNotDeletable(
        int $index
    ): ilMDConstraintTagBuilder {
        $this->indices_not_deletable[] = $index;
        return $this;
    }

    public function addNotEditable(
        int $index
    ): ilMDConstraintTagBuilder {
        $this->indices_not_editable[] = $index;
        return $this;
    }

    public function getTag(): ilMDConstraintTag
    {
        return new ilMDConstraintTag(
            $this->long_input,
            $this->preset_values,
            $this->indices_not_deletable,
            $this->indices_not_editable
        );
    }
}
