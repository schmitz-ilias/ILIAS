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
class ilMDConstraintTag extends ilMDTag
{
    protected bool $long_input;

    /**
     * Keys are indices of affected elements.
     * @var string[]
     */
    protected array $preset_values;

    /**
     * @var int[]
     */
    protected array $indices_not_deletable;

    /**
     * @var int[]
     */
    protected array $indices_not_editable;

    public function __construct(
        bool $long_input,
        array $preset_values,
        array $indices_not_deletable,
        array $indices_not_editable
    ) {
        $this->long_input = $long_input;
        $this->preset_values = $preset_values;
        $this->indices_not_deletable = $indices_not_deletable;
        $this->indices_not_editable = $indices_not_editable;
    }

    public function isLongInput(): bool
    {
        return $this->long_input;
    }

    /**
     * Keys of the returned array are the indices of the elements
     * that have their values preset.
     * @return string[]
     */
    public function getPresetValues(): array
    {
        return $this->preset_values;
    }

    /**
     * @return int[]
     */
    public function getIndicesNotDeletable(): array
    {
        return $this->indices_not_deletable;
    }

    /**
     * @return int[]
     */
    public function getIndicesNotEditable(): array
    {
        return $this->indices_not_editable;
    }
}
