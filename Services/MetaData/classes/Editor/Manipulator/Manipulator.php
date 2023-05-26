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

namespace ILIAS\MetaData\Editor\Manipulator;

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
use ILIAS\MetaData\Paths\Steps\StepToken;
use ILIAS\MetaData\Paths\Navigator\NavigatorInterface;

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
        ?PathInterface $path = null
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
                $element->addScaffoldsToSubElements($this->repository->scaffolds());
                $next = array_merge(
                    $next,
                    iterator_to_array($element->getSubElements())
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
        $navigator = $this->navigator_factory->navigator(
            $path,
            $root = $set->getRoot()
        );

        $element = $root;
        $anchor = null;
        $navigator_at_anchor = null;
        $backup_anchor = $root;
        $navigator_at_backup_anchor = $navigator;
        $stop_adding = false;
        $final_elements = [];
        /*
         * Follow the path the first time adding scaffolds where necessary,
         * remembering the best elements along the way to add more scaffolds.
         */
        while ($next = $navigator->nextStep()) {
            if (!($next_element = $next->lastElement())) {
                if ($stop_adding) {
                    break;
                }
                $next_element = $this->addAndMarkScaffoldByStep(
                    $element,
                    $next->currentStep()
                );
            }
            if (!isset($next_element)) {
                throw new \ilMDEditorException('Invalid update path: ' . $path->toString());
            }
            if ($next->currentStep()->name() === StepToken::SUPER) {
                $anchor = $backup_anchor;
                $navigator_at_anchor = $navigator_at_backup_anchor;
                $stop_adding = true;
            }
            if (!$next_element->getDefinition()->unqiue()) {
                $backup_anchor = $element;
                $navigator_at_backup_anchor = $navigator;
            }
            $navigator = $next;
            $element = $next_element;
        }
        if (!$stop_adding) {
            $final_elements = iterator_to_array($navigator->elements());
        }

        /*
         * If there are not yet enough elements to accomodate all values that
         * are to be updated/added, add them as scaffolds, starting from the
         * previously chosen anchor elements.
         */
        if (!isset($anchor) || !isset($navigator_at_anchor)) {
            $anchor = $backup_anchor;
            $navigator_at_anchor = $navigator_at_backup_anchor;
        }
        while (count($final_elements) < count($values)) {
            $scaffold = $this->addScaffoldsByNavigator(
                $anchor,
                $navigator_at_anchor
            );
            if (is_null($scaffold)) {
                throw new \ilMDEditorException('Invalid update path: ' . $path->toString());
            }
            $final_elements[] = $scaffold;
        }

        /*
         * Mark all final elements to be created/updated with the given values.
         */
        foreach ($final_elements as $element) {
            if (!($element instanceof MarkableInterface)) {
                continue;
            }
            $element->mark(
                $this->marker_factory,
                Action::CREATE_OR_UPDATE,
                is_array($values) ? array_shift($values) : $values
            );
            if (empty($values)) {
                break;
            }
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

    protected function addScaffoldsByNavigator(
        ElementInterface $start_element,
        NavigatorInterface $navigator
    ): ?ElementInterface {
        $scaffold = null;
        while ($next = $navigator->nextStep()) {
            $scaffold = $this->addAndMarkScaffoldByStep(
                $scaffold ?? $start_element,
                $next->currentStep()
            );
            if (!isset($scaffold)) {
                return null;
            }
            $navigator = $next;
        }
        return $scaffold;
    }

    /**
     * also returns the added scaffold, if valid
     */
    protected function addAndMarkScaffoldByStep(
        ElementInterface $element,
        StepInterface $step
    ): ?ElementInterface {
        if ($step->name() === StepToken::SUPER) {
            return $element->getSuperElement();
        }
        if (!($element instanceof ScaffoldableInterface)) {
            return null;
        }
        $scaffold = $element->addScaffoldToSubElements(
            $this->repository->scaffolds(),
            $step->name()
        );
        if (!isset($scaffold)) {
            return null;
        }

        $data = '';
        foreach ($step->filters() as $filter) {
            if ($filter->type() === FilterType::DATA) {
                $data = $filter->values()->current();
                break;
            }
        }
        $scaffold->mark(
            $this->marker_factory,
            Action::CREATE_OR_UPDATE,
            $data
        );

        return $scaffold;
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
        ?PathInterface $path = null
    ): \Generator {
        if (!isset($path)) {
            yield $set->getRoot();
            return;
        }
        yield from $this->navigator_factory->navigator(
            $path,
            $set->getRoot()
        )->elementsAtFinalStep();
    }
}
