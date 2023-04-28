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

use ILIAS\MetaData\Elements\Base\BaseElementInterface;
use ILIAS\MetaData\Paths\Filters\FilterType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Factory implements FactoryInterface
{
    public function fromString(string $string): Path
    {
    }

    public function toElement(
        BaseElementInterface $to,
        bool $leads_to_exactly_one = false
    ): Path {
        $builder = $this
            ->custom()
            ->withRelative(false)
            ->withLeadsToExactlyOneElement($leads_to_exactly_one);

        while (!$to->isRoot()) {
            $builder = $this->addElementAsStep(
                $builder,
                $to,
                $leads_to_exactly_one
            );
            $to = $to->getSuperElement();
            if (!isset($to)) {
                throw new \ilMDPathException(
                    'Cannot build path from element without root.'
                );
            }
        }

        return $builder->get();
    }

    public function betweenElements(
        BaseElementInterface $from,
        BaseElementInterface $to,
        bool $leads_to_exactly_one = false
    ): Path {
        $to_and_supers = [];
        while ($to) {
            array_unshift($to_and_supers, $to);
            $to = $to->getSuperElement();
        }

        $builder = $this
            ->custom()
            ->withRelative(true)
            ->withLeadsToExactlyOneElement($leads_to_exactly_one);

        while (!in_array($from, $to_and_supers, true)) {
            $builder = $builder->withNextStepToSuperElement();
            $from = $from->getSuperElement();
            if (!isset($from)) {
                throw new \ilMDPathException(
                    'Cannot build path between elements from disjunct sets.'
                );
            }
        }

        $to_and_supers = array_slice(
            $to_and_supers,
            array_search($from, $to_and_supers, true) + 1
        );
        foreach ($to_and_supers as $element) {
            $builder = $this->addElementAsStep(
                $builder,
                $element,
                $leads_to_exactly_one
            );
        }

        return $builder->get();
    }

    protected function addElementAsStep(
        Builder $builder,
        BaseElementInterface $element,
        bool $leads_to_exactly_one
    ): Builder {
        $builder = $builder->withNextStep(
            $element->getDefinition(),
            true
        );

        $id = $element->getMDID();
        if ($leads_to_exactly_one && is_int($id)) {
            $builder = $builder->withAdditionalFilterAtCurrentStep(
                FilterType::MDID,
                (string) $id
            );
        } else {
            $builder = $builder->withLeadsToExactlyOneElement(false);
        }

        return $builder;
    }

    public function custom(): Builder
    {
        return new Builder();
    }
}
