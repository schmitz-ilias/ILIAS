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

use ILIAS\UI\Component\Input\Field\Factory as UIFactory;
use ILIAS\UI\Component\Input\Field\FormInput;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Repository\Validation\Dictionary\DictionaryInterface as ConstraintDictionary;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\Data\DataInterface;
use ILIAS\MetaData\Elements\Data\Type;
use ILIAS\MetaData\Repository\Validation\Dictionary\Restriction;
use ILIAS\MetaData\Repository\Validation\Dictionary\TagInterface as ConstraintTag;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
abstract class BaseFactory
{
    protected UIFactory $ui_factory;
    protected PresenterInterface $presenter;
    protected ConstraintDictionary $constraint_dictionary;

    public function __construct(
        UIFactory $ui_factory,
        PresenterInterface $presenter,
        ConstraintDictionary $constraint_dictionary
    ) {
        $this->ui_factory = $ui_factory;
        $this->presenter = $presenter;
        $this->constraint_dictionary = $constraint_dictionary;
    }

    abstract protected function rawInput(
        ElementInterface $element,
        ElementInterface $context_element,
        string $condition_value = ''
    ): FormInput;

    protected function conditionInput(
        ElementInterface $element,
        ElementInterface $context_element,
        ElementInterface $conditional_element
    ): FormInput {
        throw new \ilMDEditorException(
            'Only vocabulary values can serve as conditions.'
        );
    }

    public function getConditionElement(
        ElementInterface $element
    ): ?ElementInterface {
        return null;
    }

    /**
     * @return string|string[]
     */
    protected function dataValueForInput(
        DataInterface $data
    ): string|array {
        return $data->value();
    }

    final public function getInput(
        ElementInterface $element,
        ElementInterface $context_element,
        ?ElementInterface $conditional_element = null,
        string $condition_value = ''
    ): FormInput {
        $label = $this->presenter->elements()->nameWithParents(
            $element,
            $context_element,
            false
        );
        $input = isset($conditional_element) ?
            $this->conditionInput($element, $context_element, $conditional_element) :
            $this->rawInput($element, $context_element);
        $input = $input->withLabel($label);

        if (($data = $element->getData())->type() !== Type::NULL) {
            $input = $input->withValue(
                $this->dataValueForInput($data)
            );
        }

        foreach ($this->constraint_dictionary->tagsForElement($element) as $tag) {
            $this->addConstraintFromTag($input, $tag);
        }

        return $input;
    }

    protected function addConstraintFromTag(
        FormInput $input,
        ConstraintTag $tag
    ): FormInput {
        switch ($tag->restriction()) {
            case Restriction::PRESET_VALUE:
                return $input->withValue($tag->value());

            case Restriction::NOT_DELETABLE:
                return $input->withRequired(true);

            case Restriction::NOT_EDITABLE:
                return $input->withDisabled(true);
        }
        return $input;
    }
}
