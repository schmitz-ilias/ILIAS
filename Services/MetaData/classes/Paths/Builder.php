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

namespace ILIAS\MetaData\Paths;

use ILIAS\MetaData\Paths\Filters\FilterType;
use ILIAS\MetaData\Elements\Definition\DefinitionInterface;
use ILIAS\MetaData\Paths\Steps\Step;
use ILIAS\MetaData\Paths\Filters\Filter;
use ILIAS\MetaData\Paths\Steps\StepToken;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Builder implements BuilderInterface
{
    /**
     * @var Step[]
     */
    protected array $steps = [];

    protected string|StepToken|null $current_step_name = null;

    /**
     * @var Filter[]
     */
    protected array $current_step_filters = [];
    protected bool $current_add_as_first = false;

    protected bool $is_relative = false;
    protected bool $leads_to_one = false;

    public function withRelative(bool $is_relative): Builder
    {
        $clone = clone $this;
        $clone->is_relative = $is_relative;
        return $clone;
    }

    public function withLeadsToExactlyOneElement(
        bool $leads_to_one
    ): Builder {
        $clone = clone $this;
        $clone->leads_to_one = $leads_to_one;
        return $clone;
    }

    public function withNextStep(
        DefinitionInterface $definition,
        bool $add_as_first = false
    ): Builder {
        return $this->withNextStepFromName(
            $definition->name(),
            $add_as_first
        );
    }

    public function withNextStepToSuperElement(bool $add_as_first = false): Builder
    {
        return $this->withNextStepFromName(
            StepToken::SUPER,
            $add_as_first
        );
    }

    protected function withNextStepFromName(
        string|StepToken $name,
        bool $add_as_first
    ): Builder {
        $clone = $this->withCurrentStepSaved();
        $clone->current_step_name = $name;
        $clone->current_add_as_first = $add_as_first;
        return $clone;
    }

    public function withAdditionalFilterAtCurrentStep(
        FilterType $type,
        string ...$values
    ): Builder {
        if (!isset($this->current_step_name)) {
            throw new \ilMDPathException(
                'Cannot add filter because there is no current step.'
            );
        }
        $clone = clone $this;
        $clone->current_step_filters[] = new Filter(
            $type,
            ...$values
        );
        return $clone;
    }

    public function get(): Path
    {
        $clone = $this->withCurrentStepSaved();
        return new Path(
            $clone->is_relative,
            $clone->leads_to_one,
            ...$clone->steps
        );
    }

    protected function withCurrentStepSaved(): Builder
    {
        $clone = clone $this;
        if (!isset($clone->current_step_name)) {
            return $clone;
        }

        $new_step = new Step(
            $clone->current_step_name,
            ...$clone->current_step_filters
        );
        if ($clone->current_add_as_first) {
            array_unshift($clone->steps, $new_step);
        } else {
            $clone->steps[] = $new_step;
        }

        $clone->current_step_name = null;
        $clone->current_step_filters = [];
        $clone->current_add_as_first = false;

        return $clone;
    }
}
