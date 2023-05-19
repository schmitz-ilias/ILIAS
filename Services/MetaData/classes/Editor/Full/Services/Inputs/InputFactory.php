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
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Component\Input\Field\Factory as UIFactory;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\MetaData\Editor\Presenter\PresenterInterface;
use ILIAS\MetaData\Repository\Dictionary\DictionaryInterface as DatabaseDictionary;
use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Repository\Dictionary\ExpectedParameter;
use ILIAS\MetaData\Editor\Full\Services\DataFinder;
use ILIAS\MetaData\Paths\FactoryInterface as PathFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class InputFactory
{
    protected UIFactory $ui_factory;
    protected Refinery $refinery;
    protected PresenterInterface $presenter;
    protected PathFactory $path_factory;
    protected DataFinder $data_finder;
    protected FactoryTypesService $types;

    /**
     * This is only here because the
     * editor needs to know which elements can be created (meaning
     * have a non-null create query).
     * This should be changed when we change the DB structure to
     * something that can work better with the new editor.
     */
    protected DatabaseDictionary $db_dictionary;

    public function __construct(
        UIFactory $ui_factory,
        Refinery $refinery,
        PresenterInterface $presenter,
        PathFactory $path_factory,
        DataFinder $data_finder,
        DatabaseDictionary $db_dictionary,
        FactoryTypesService $types
    ) {
        $this->ui_factory = $ui_factory;
        $this->refinery = $refinery;
        $this->presenter = $presenter;
        $this->path_factory = $path_factory;
        $this->data_finder = $data_finder;
        $this->db_dictionary = $db_dictionary;
        $this->types = $types;
    }

    public function getInputFields(
        ElementInterface $element,
        ElementInterface $context_element,
        bool $with_title
    ): Section|Group {
        $inputs = [];
        $exclude_required = [];
        foreach ($this->data_finder->getDataCarryingElements($element) as $data_carrier) {
            $input = $this->types->factory($data_carrier->getDefinition()->dataType())->getInput(
                $data_carrier,
                $context_element
            );
            $path_string = $this->path_factory->toElement($data_carrier, true)->toString();
            $inputs[$path_string] = $input;

            /**
             * If a data element can't be created, it needs to be excluded
             * from checking whether at least one input field is not empty.
             */
            if ($this->db_dictionary->tagForElement($data_carrier)->create() === '') {
                $exclude_required[] = $path_string;
            }
        }

        if ($with_title) {
            $fields = $this->ui_factory->section(
                $inputs,
                $this->presenter->elements()->nameWithParents($context_element)
            );
        } else {
            $fields = $this->ui_factory->group($inputs);
        }

        // flatten the output of conditional inputs
        $fields = $fields->withAdditionalTransformation(
            $this->refinery->custom()->transformation(function ($vs) {
                foreach ($vs as $key => $v) {
                    if (is_array($v)) {
                        $vs[$key] = $v[self::COND_VALUE]['value'];
                        $vs[$v[self::COND_VALUE]['path']] = $v[self::VALUE];
                    }
                }
                return $vs;
            })
        );

        return $this->addNotEmptyConstraintIfNeeded(
            $context_element,
            $fields,
            ...$exclude_required
        );
    }

    /**
     * If the current element can't be created on its own due to the db
     * structure, the editor has to require that at least one of the
     * inputs is not empty.
     */
    protected function addNotEmptyConstraintIfNeeded(
        ElementInterface $context_element,
        Section|Group $fields,
        string ...$excluded_input_keys
    ): Section|Group {
        $db_tag = $this->db_dictionary->tagForElement($context_element);
        $needs_data = false;
        foreach ($db_tag->expectedParameters() as $parameter) {
            if ($parameter === ExpectedParameter::DATA) {
                $needs_data = true;
                break;
            }
        }
        if ($db_tag->create() !== '' && !$needs_data) {
            return $fields;
        }
        return $fields->withAdditionalTransformation(
            $this->refinery->custom()->constraint(
                function ($vs) use ($excluded_input_keys) {
                    foreach ($vs as $p => $v) {
                        if (in_array($p, $excluded_input_keys)) {
                            continue;
                        }
                        if ($v !== '' && $v !== null) {
                            return true;
                        }
                    }
                    return false;
                },
                $this->presenter->utilities()->txt('meta_error_empty_input')
            )
        );
    }
}
