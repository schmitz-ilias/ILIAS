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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class VocabValueFactory extends BaseFactory
{
    protected VocabulariesInterface $vocabularies;

    public function __construct(
        UIFactory $ui_factory,
        PresenterInterface $presenter,
        ConstraintDictionary $constraint_dictionary,
        VocabulariesInterface $vocabularies
    ) {
        parent::__construct($ui_factory, $presenter, $constraint_dictionary);
        $this->vocabularies = $vocabularies;
    }

    protected function rawInput(
        ElementInterface $element,
        ElementInterface $context_element
    ): FormInput {
        $values = [];
        foreach ($this->vocabularies->vocabulariesForElement($element) as $vocab) {
            foreach ($vocab->values() as $value) {
                $values[$value] = $this->presenter->data()->vocabularyValue($value);
            }
        }
        return $this->ui_factory->select('placeholder', $values);
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
