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

namespace ILIAS\MetaData\Editor\Full\Services\Inputs;

use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Component\Input\Field\FormInput;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\UI\Component\Input\Field\Factory as UIFactory;
use ILIAS\MetaData\Repository\Validation\Dictionary\DictionaryInterface as ConstraintDictionary;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Elements\Data\DataInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class InputWithConditionFactory extends BaseFactory
{
    protected Refinery $refinery;

    public function __construct(
        UIFactory $ui_factory,
        PresenterInterface $presenter,
        ConstraintDictionary $constraint_dictionary,
        Refinery $refinery
    ) {
        parent::__construct($ui_factory, $presenter, $constraint_dictionary);
        $this->refinery = $refinery;
    }

    protected function rawInput(
        ElementInterface $element,
        ElementInterface $context_element
    ): FormInput {
        $selects = [];
        foreach ($tag->getVocabularies() as $vocab) {
            if ($default === '') {
                $default = $vocab->conditionValue();
            }
            $v = $current_element->isScaffold() ?
                '' : $this->getDataValueForInput(
                    $current_element->getData(),
                    $this->presenter
                );
            $v = in_array($v, $vocab->values()) ? $v : '';
            $selects[$vocab->conditionValue()] = $this->ui_factory
                ->select(
                    $this->presenter->getElementNameWithParents(
                        $current_element,
                        false,
                        $element->getName()
                    ),
                    array_combine(
                        $vocab->values(),
                        $this->presenter->getVocab($vocab)
                    )
                )->withValue($v);
        }
        $cond_el = $this->getConditionElement(
            $current_element,
            $tag->getConditionPath()
        );
        $stored_path = $this->appendPath(
            $path,
            $element,
            $current_element
        )->getPathAsString();
        $cond_tag = $this->getElementVocabTagFromStructure(
            $cond_el
        );
        $vocab = $cond_tag->getVocabularies()[0];
        $groups = [];
        foreach ($vocab->values() as $value) {
            $groups[$value] = $this->ui_factory->group(
                isset($selects[$value]) ? [$selects[$value]] : [],
                $this->presenter->getVocabValue($value)
            );
        }
        $res = $this->ui_factory->switchableGroup(
            $groups,
            'placeholder'
        )->withAdditionalTransformation(
            $this->refinery->custom()->transformation(
                function ($vs) use ($stored_path) {
                    $r[self::COND_VALUE] = [
                        'path' => $stored_path,
                        'value' => $vs[0]
                    ];
                    $r[self::VALUE] = $vs[1][0];
                    return $r;
                }
            )
        );
        $current_element = $cond_el;
    }
}
