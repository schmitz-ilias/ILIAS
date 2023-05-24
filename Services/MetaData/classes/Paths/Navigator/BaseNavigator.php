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
use ILIAS\MetaData\Paths\Steps\NavigatorBridge;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Paths\Steps\StepInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
abstract class BaseNavigator implements BaseNavigatorInterface
{
    private NavigatorBridge $bridge;

    /**
     * @var BaseElementInterface[]
     */
    private array $elements;

    /**
     * @var StepInterface[]
     */
    private array $remaining_steps;
    private ?StepInterface $current_step = null;
    private bool $leads_to_one;

    public function __construct(
        PathInterface $path,
        BaseElementInterface $start_element,
        NavigatorBridge $bridge
    ) {
        $this->bridge = $bridge;

        $this->remaining_steps = iterator_to_array($path->steps());
        $this->leadsToOne($path->leadsToExactlyOneElement());
        if ($path->isRelative()) {
            $this->elements = [$start_element];
            return;
        }
        while (!$start_element->isRoot()) {
            $start_element = $start_element->getSuperElement();
            if (!isset($start_element)) {
                throw new \ilMDPathException(
                    'Can not navigate on an invalid metadata set.'
                );
            }
        }
        $this->elements = [$start_element];
    }

    protected function leadsToOne(bool $leads_to_one): void
    {
        $this->leads_to_one = $leads_to_one;
    }

    public function currentStep(): StepInterface
    {
        return $this->current_step;
    }

    public function nextStep(): ?BaseNavigatorInterface
    {
        if (empty($this->remaining_steps)) {
            return null;
        }
        $clone = clone $this;

        $new_elements = [];
        foreach ($clone->elements as $element) {
            $filtered_sub_elements = $clone->bridge->getNextElementsByStep(
                $element,
                $clone->remaining_steps[0]
            );
            $new_elements = array_merge(
                $new_elements,
                iterator_to_array($filtered_sub_elements)
            );
        }

        $clone->current_step = $clone->remaining_steps[0];
        array_shift($clone->remaining_steps);
        $clone->elements = $new_elements;
        return $clone;
    }

    /**
     * @return BaseElementInterface[]
     * @throws \ilMDPathException
     */
    public function elementsAtFinalStep(): \Generator
    {
        $clone = clone $this;
        while ($next = $clone->nextStep()) {
            $clone = $next;
        }
        yield from $clone->elements();
    }

    /**
     * @throws \ilMDPathException
     */
    public function lastElementAtFinalStep(): ?BaseElementInterface
    {
        $return = null;
        foreach ($this->elementsAtFinalStep() as $element) {
            $return = $element;
        }
        return $return;
    }

    /**
     * @return BaseElementInterface[]
     * @throws \ilMDPathException
     */
    public function elements(): \Generator
    {
        $this->checkLeadsToOne();
        yield from $this->elements;
    }

    /**
     * @throws \ilMDPathException
     */
    public function lastElement(): ?BaseElementInterface
    {
        $return = null;
        foreach ($this->elements() as $element) {
            $return = $element;
        }
        return $return;
    }

    /**
     * @throws \ilMDPathException
     */
    protected function checkLeadsToOne(): void
    {
        if (!$this->leads_to_one) {
            return;
        }
        if (count($this->elements) !== 1) {
            throw new \ilMDPathException(
                'Path should lead to exactly one element but does not.'
            );
        }
    }
}
