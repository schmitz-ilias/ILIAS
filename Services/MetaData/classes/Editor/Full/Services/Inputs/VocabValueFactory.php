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

use ILIAS\UI\Component\Input\Field\FormInput;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Elements\Data\DataInterface;
use ILIAS\MetaData\Vocabularies\VocabulariesInterface;
use ILIAS\UI\Component\Input\Field\Factory as UIFactory;
use ILIAS\MetaData\Repository\Validation\Dictionary\DictionaryInterface as ConstraintDictionary;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Elements\Data\Type;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class VocabValueFactory extends BaseFactory
{
    protected VocabulariesInterface $vocabularies;
    protected PathFactory $path_factory;

    public function __construct(
        UIFactory $ui_factory,
        PresenterInterface $presenter,
        ConstraintDictionary $constraint_dictionary,
        VocabulariesInterface $vocabularies,
        PathFactory $path_factory
    ) {
        parent::__construct($ui_factory, $presenter, $constraint_dictionary);
        $this->vocabularies = $vocabularies;
        $this->path_factory = $path_factory;
    }

    protected function rawInput(
        ElementInterface $element,
        ElementInterface $context_element,
        string $condition_value = ''
    ): FormInput {
        if ($element->getDefinition()->dataType() !== Type::VOCAB_VALUE) {
            throw new \ilMDEditorException('Invalid data type for conditional input.');
        }
        $values = [];
        foreach ($this->vocabularies->vocabulariesForElement($element) as $vocab) {
            if ($condition_value !== '' && $vocab->condition()?->value() !== $condition_value) {
                continue;
            }
            foreach ($vocab->values() as $value) {
                $values[$value] = $this->presenter->data()->vocabularyValue($value);
            }
        }
        return $this->ui_factory->select('placeholder', $values);
    }

    protected function conditionInput(
        ElementInterface $element,
        ElementInterface $context_element,
        ElementInterface $conditional_element
    ): FormInput {
        $groups = [];
        foreach ($this->vocabularies->vocabulariesForElement($element) as $vocab) {
            foreach ($vocab->values() as $value) {
                $input = $this->getInput(
                    $conditional_element,
                    $context_element,
                    null,
                    $value
                );
                $path_string = $this->path_factory->toElement($conditional_element, true)
                                                  ->toString();
                $groups[$value] = $this->ui_factory->group(
                    [$path_string => $input],
                    $this->presenter->data()->vocabularyValue($value)
                );
            }
        }
        return $this->ui_factory->switchableGroup(
            $groups,
            'placeholder'
        );
    }

    protected function dataValueForInput(DataInterface $data): string
    {
        $value = strtolower(
            preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $data->value())
        );
        $exceptions = [
            'is part of' => 'ispartof', 'has part' => 'haspart',
            'is version of' => 'isversionof', 'has version' => 'hasversion',
            'is format of' => 'isformatof', 'has format' => 'hasformat',
            'references' => 'references',
            'is referenced by' => 'isreferencedby',
            'is based on' => 'isbasedon', 'is basis for' => 'isbasisfor',
            'requires' => 'requires', 'is required by' => 'isrequiredby',
        ];
        if (array_key_exists($value, $exceptions)) {
            $value = $exceptions[$value];
        }
        return $value;
    }
}
