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

namespace ILIAS\MetaData\Paths\Steps;

use ILIAS\MetaData\Paths\Filters\FilterInterface;
use ILIAS\MetaData\Paths\Filters\FilterType;
use ILIAS\MetaData\Elements\NoID;
use ILIAS\MetaData\Elements\Data\DataCarrierInterface;
use ILIAS\MetaData\Elements\Base\BaseElementInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class NavigatorBridge
{
    /**
     * @return BaseElementInterface[]
     */
    public function getSubElementsFromStep(
        BaseElementInterface $element,
        StepInterface $step
    ): \Generator {
        $index_filters = [];
        $single_element_filters = [];

        foreach ($step->filters() as $filter) {
            if ($filter->type() === FilterType::INDEX) {
                $index_filters[] = $filter;
                continue;
            }
            $single_element_filters = [];
        }

        $filtered_by_index = $this->filterSubElementsByNameAndIndex(
            $element,
            $step->name(),
            ...$index_filters
        );
        foreach ($filtered_by_index as $element) {
            if ($this->matchesSingleElementFilters(
                $element,
                ...$single_element_filters
            )) {
                yield $element;
            }
        }
    }

    /**
     * @return BaseElementInterface[]
     */
    protected function filterSubElementsByNameAndIndex(
        BaseElementInterface $element,
        string $name,
        FilterInterface ...$filters
    ): \Generator {
        $index = 0;
        foreach ($element->getSubElements() as $sub_element) {
            $element_name = $sub_element->getDefinition()->name();
            if ($element_name !== $name) {
                continue;
            }
            $index++;
            if ($this->indexMatchesFilters($index, ...$filters)) {
                yield $sub_element;
            }
        }
    }

    protected function indexMatchesFilters(
        int $index,
        FilterInterface ...$filters
    ): bool {
        foreach ($filters as $filter) {
            if (!in_array($index, $filter->values())) {
                return false;
            }
        }
        return true;
    }

    protected function matchesSingleElementFilters(
        BaseElementInterface $element,
        FilterInterface ...$filters
    ): bool {
        foreach ($filters as $filter) {
            $match = true;

            switch ($filter->type()) {
                case FilterType::NULL:
                    break;

                case FilterType::MDID:
                    $match = $this->MDIDMatchesFilter($element, $filter);
                    break;

                case FilterType::INDEX:
                    throw new \ilMDPathException(
                        'Index filters can not be performed on a single element.'
                    );

                case FilterType::DATA:
                    $match = $this->DataMatchesFilter($element, $filter);
                    break;
            }

            if (!$match) {
                return false;
            }
        }

        return true;
    }

    protected function MDIDMatchesFilter(
        BaseElementInterface $element,
        FilterInterface $filter
    ): bool {
        $id = $element->getMDID();
        if (is_int($id)) {
            $id = (string) $id;
        } elseif ($id instanceof NoID) {
            $id = $id->value;
        }
        if (in_array($id, $filter->values())) {
            return true;
        }
        return false;
    }

    protected function dataMatchesFilter(
        BaseElementInterface $element,
        FilterInterface $filter
    ): bool {
        if (!($element instanceof DataCarrierInterface)) {
            return false;
        }
        if (in_array($element->getData()->value(), $filter->values())) {
            return true;
        }
        return false;
    }
}
