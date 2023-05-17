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

namespace ILIAS\MetaData\Editor\Services\Manipulator;

use ILIAS\MetaData\Repository\RepositoryInterface;
use ILIAS\MetaData\Elements\Markers\MarkerFactoryInterface;
use ILIAS\MetaData\Paths\Navigator\NavigatorFactoryInterface;
use ILIAS\MetaData\Elements\SetInterface;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\Scaffolds\ScaffoldableInterface;
use ILIAS\MetaData\Elements\Markers\MarkableInterface;
use ILIAS\MetaData\Elements\Markers\Action;
use ILIAS\MetaData\Paths\Steps\StepInterface;
use ILIAS\MetaData\Paths\Filters\FilterType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Manipulator implements ManipulatorInterface
{
    protected RepositoryInterface $repository;
    protected MarkerFactoryInterface $marker_factory;
    protected NavigatorFactoryInterface $navigator_factory;

    public function __construct(
        RepositoryInterface $repository,
        MarkerFactoryInterface $marker_factory,
        NavigatorFactoryInterface $navigator_factory
    ) {
        $this->repository = $repository;
        $this->marker_factory = $marker_factory;
        $this->navigator_factory = $navigator_factory;
    }

    public function addScaffolds(
        SetInterface $set,
        PathInterface $path
    ): SetInterface {
        $set = clone $set;
        $to_be_scaffolded = [];
        foreach ($this->getElements($set, $path) as $el) {
            $super = $el->getSuperElement() ?? $el;
            if (!in_array($super, $to_be_scaffolded, true)) {
                $to_be_scaffolded[] = $super;
            }
        }
        while (!empty($to_be_scaffolded)) {
            $next = [];
            foreach ($to_be_scaffolded as $element) {
                if (!($element instanceof ScaffoldableInterface)) {
                    continue;
                }
                $scaffolds = $this->repository->getScaffoldsForElement($element);
                foreach ($scaffolds as $scaffold) {
                    $element->addScaffoldToSubElements($scaffold);
                }
                $next = array_merge(
                    $next,
                    $element->getSubElements()
                );
            }
            $to_be_scaffolded = $next;
        }
        return $set;
    }

    public function prepareCreateOrUpdate(
        SetInterface $set,
        PathInterface $path,
        string ...$values
    ): SetInterface {
        $set = clone $set;
        $current_super = $set->getRoot();
        $navigator = $this->navigator_factory->navigator(
            $path,
            $current_super
        )->nextStep();
        // move along the path except the final step, adding scaffolds where necessary
        while ($next = $navigator->nextStep()) {
            if ($element = $navigator->lastElement()) {
                $current_super = $element;
            } else {
                $current_super = $this->addAndMarkScaffoldByStep(
                    $current_super,
                    $navigator->currentStep()
                );
            }
            if (is_null($current_super)) {
                throw new \ilMDEditorException('Invalid update path.');
            }
            $navigator = $next;
        }
        // add as many scaffolds as necessary to the final step
        $final_elements = [];
        foreach ($navigator->elements() as $element) {
            $final_elements[] = $element;
        }
        while (count($final_elements) < count($values)) {
            $scaffold = $this->addAndMarkScaffoldByStep(
                $current_super,
                $navigator->currentStep()
            );
            if (is_null($scaffold)) {
                throw new \ilMDEditorException('Invalid update path.');
            }
            $final_elements[] = $scaffold;
        }
        // mark all final elements
        foreach ($final_elements as $element) {
            if (!($element instanceof MarkableInterface)) {
                continue;
            }
            $element->mark(
                $this->marker_factory,
                Action::CREATE_OR_UPDATE,
                array_shift($values) ?? ''
            );
        }
        return $set;
    }

    public function prepareDelete(
        SetInterface $set,
        PathInterface $path,
    ): SetInterface {
        $set = clone $set;
        foreach ($this->getMarkables($set, $path) as $element) {
            $element->mark($this->marker_factory, Action::DELETE);
        }
        return $set;
    }

    public function execute(SetInterface $set): void
    {
        $this->repository->manipulateMD($set);
    }

    /**
     * also returns the added scaffold, if valid
     */
    protected function addAndMarkScaffoldByStep(
        ElementInterface $element,
        StepInterface $step
    ): ?ElementInterface {
        foreach ($this->repository->getScaffoldsForElement($element) as $scaffold) {
            if (
                $scaffold->getDefinition()->name() === $step->name() &&
                $element instanceof ScaffoldableInterface
            ) {
                $scaffold_with_name = $scaffold;
                break;
            }
        }
        if (!isset($scaffold_with_name)) {
            return null;
        }
        foreach ($step->filters() as $filter) {
            if ($filter->type() === FilterType::DATA) {
                $scaffold_with_name->mark(
                    $this->marker_factory,
                    Action::CREATE_OR_UPDATE,
                    $filter->values()->current()
                );
                break;
            }
        }
        $element->addScaffoldToSubElements($scaffold_with_name);
        return $scaffold_with_name;
    }

    /**
     * @return MarkableInterface
     */
    protected function getMarkables(
        SetInterface $set,
        PathInterface $path
    ): \Generator {
        foreach ($this->getElements($set, $path) as $element) {
            if ($element instanceof MarkableInterface) {
                yield $element;
            }
        }
    }

    /**
     * @return ElementInterface[]
     */
    protected function getElements(
        SetInterface $set,
        PathInterface $path
    ): \Generator {
        yield from $this->navigator_factory->navigator(
            $path,
            $set->getRoot()
        )->elementsAtFinalStep();
    }
}
