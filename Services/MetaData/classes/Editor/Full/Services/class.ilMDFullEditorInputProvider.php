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

use ILIAS\UI\Component\Input\Field\FormInput;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Component\Input\Field\Factory;
use ILIAS\Refinery\Factory as Refinery;
use classes\Elements\Data\ilMDLOMDataFactory;
use classes\Elements\Data\ilMDData;
use classes\Elements\ilMDBaseElement;
use classes\Elements\ilMDRootElement;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDFullEditorInputProvider
{
    protected const COND_VALUE = 'md_cond_value';
    protected const VALUE = 'md_value';

    protected Factory $factory;
    protected Refinery $refinery;
    protected ilMDLOMPresenter $presenter;
    protected ilMDLOMVocabulariesDictionary $vocab_dict;
    protected ilMDLOMConstraintDictionary $constraint_dict;
    protected ilMDLOMEditorGUIDictionary $ui_dict;
    protected DataFinder $data_finder;

    /**
     * This is only here because the
     * editor needs to know which elements can be created (meaning
     * have a non-null create query), but this does not contain
     * actually functioning queries.
     * This should be changed when we change the DB structure to
     * something that can work better with the new editor.
     */
    protected ilMDLOMDatabaseDictionary $db_dict;

    public function __construct(
        Factory $factory,
        Refinery $refinery,
        ilMDLOMPresenter $presenter,
        ilMDLOMVocabulariesDictionary $vocab_dict,
        ilMDLOMConstraintDictionary $constraint_dict,
        ilMDLOMEditorGUIDictionary $ui_dict,
        DataFinder $data_finder,
        ilMDLOMDatabaseDictionary $db_dict
    ) {
        $this->factory = $factory;
        $this->refinery = $refinery;
        $this->presenter = $presenter;
        $this->vocab_dict = $vocab_dict;
        $this->constraint_dict = $constraint_dict;
        $this->ui_dict = $ui_dict;
        $this->data_finder = $data_finder;
        $this->db_dict = $db_dict;
    }

    public function getInputFields(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        ilMDPathFromRoot $action_path,
        bool $with_title
    ): Section|Group {
        $element = $this->getUniqueElement($root, $path);
        $data_els = $this->data_finder->getDataCarryingElements($element);
        $inputs = [];
        $exclude_required = [];
        foreach ($data_els as $data_el) {
            global $DIC;
            $arr = $this->getInputForElement(
                $root,
                $path,
                $action_path,
                $data_el
            );
            $inputs[$arr['path']->getPathAsString()] = $arr['input'];

            /**
             * If a data element can't be created, it needs to be excluded
             * from checking whether at least one input field is not empty.
             */
            $data_create = $this
                ->getElementDBTagFromStructure($data_el)
                ->getCreate();
            if ($data_create === '') {
                $exclude_required[] = $arr['path']->getPathAsString();
            }
        }

        if ($with_title) {
            $fields = $this->factory->section(
                $inputs,
                $this->presenter->getElementNameWithParents($element, false)
            );
        } else {
            $fields = $this->factory->group(
                $inputs
            );
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

        /**
         * If the current element can't be created on its own due to the db
         * structure, the editor has to require that at least one of the
         * inputs is not empty.
         */
        $tag = $this->db_dict
            ->getStructure()
            ->movePointerToEndOfPath($action_path)
            ->getTagAtPointer();
        $create = $tag->getCreate();
        $needs_data = in_array(
            ilMDLOMDatabaseDictionary::EXP_DATA,
            $tag->getExpectedParams()
        );

        if ($create === '' || $needs_data) {
            $fields = $fields->withAdditionalTransformation(
                $this->refinery->custom()->constraint(
                    function ($vs) use ($exclude_required) {
                        foreach ($vs as $p => $v) {
                            if (in_array($p, $exclude_required)) {
                                continue;
                            }
                            if ($v !== '' && $v !== null) {
                                return true;
                            }
                        }
                        return false;
                    },
                    $this->presenter->txt('meta_error_empty_input')
                )
            );
        }

        return $fields;
    }

    protected function getIndexOfElement(
        ilMDRootElement $root,
        ilMDPathFromRoot $path_to_element
    ): int {
        // remove all filters from the path
        $clean_path = clone $path_to_element;
        $steps = [];
        while (!$clean_path->isAtStart()) {
            array_unshift($steps, $clean_path->getStep());
            $clean_path->removeLastStep();
        }
        foreach ($steps as $step) {
            $clean_path->addStep($step);
        }

        // find the index
        $element = $this->getUniqueElement($root, $path_to_element);
        return 1 + array_search(
            $element,
            $root->getSubElementsByPath($clean_path),
            true
        );
    }

    protected function appendPath(
        ilMDPathFromRoot $path,
        ilMDBaseElement $old_path_end,
        ilMDBaseElement $new_path_end
    ): ilMDPathFromRoot {
        $res = clone $path;
        $steps = [];
        while (!$new_path_end->isRoot() && $new_path_end !== $old_path_end) {
            array_unshift($steps, $new_path_end->getName());
            $new_path_end = $new_path_end->getSuperElement();
        }
        foreach ($steps as $step) {
            $res->addStep($step);
        }
        return $res;
    }

    protected function getElementDataTypeFromStructure(
        ilMDBaseElement $element
    ): string {
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $structure = $this->vocab_dict->getStructure();
        $structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }
        return $structure->getTypeAtPointer();
    }

    protected function getElementDBTagFromStructure(
        ilMDBaseElement $element
    ): ilMDDatabaseTag {
        /** @var $tag ilMDDatabaseTag */
        $tag = $this->getElementTagFromStructure(
            $element,
            $this->db_dict->getStructure()
        );
        return $tag;
    }

    protected function getElementVocabTagFromStructure(
        ilMDBaseElement $element
    ): ?ilMDVocabulariesTag {
        /** @var $tag ?ilMDVocabulariesTag */
        $tag = $this->getElementTagFromStructure(
            $element,
            $this->vocab_dict->getStructure()
        );
        return $tag;
    }

    protected function getElementConstraintTagFromStructure(
        ilMDBaseElement $element
    ): ?ilMDConstraintTag {
        /** @var $tag ?ilMDConstraintTag */
        $tag = $this->getElementTagFromStructure(
            $element,
            $this->constraint_dict->getStructure()
        );
        return $tag;
    }

    protected function getElementUITagFromStructure(
        ilMDBaseElement $element
    ): ?ilMDEditorGUITag {
        /** @var $tag ?ilMDEditorGUITag */
        $tag = $this->getElementTagFromStructure(
            $element,
            $this->ui_dict->getStructure()
        );
        return $tag;
    }

    protected function getElementTagFromStructure(
        ilMDBaseElement $element,
        ilMDLOMStructure $structure,
    ): ?ilMDTag {
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }
        return $structure->getTagAtPointer();
    }

    /**
     * @return array{input: FormInput, path: ilMDPathFromRoot}
     */
    protected function getInputForElement(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        ilMDPathFromRoot $action_path,
        ilMDBaseElement $current_element,
    ): array {
        $element = $this->getUniqueElement($root, $path);
        $action_element = $this->getUniqueElement($root, $action_path);
        $type = $this->getElementDataTypeFromStructure(
            $current_element
        );
        $constraint_tag = $this->getElementConstraintTagFromStructure(
            $current_element
        );

        $default = '';
        switch ($type) {
            case ilMDLOMDataFactory::TYPE_NULL:
                throw new ilMDEditorException(
                    'Cannot generate input field for element with no data.'
                );

            case ilMDLOMDataFactory::TYPE_STRING:
                if ($constraint_tag?->isLongInput()) {
                    $res = $this->factory->textarea('placeholder');
                } else {
                    $res = $this->factory->text('placeholder');
                }
                break;

            case ilMDLOMDataFactory::TYPE_LANG:
                $res = $this->factory->select(
                    'placeholder',
                    array_combine(
                        ilMDLOMDataFactory::LANGUAGES,
                        $this->presenter->getLanguages()
                    )
                );
                break;

            case ilMDLOMDataFactory::TYPE_VOCAB_VALUE:
                $tag = $this->getElementVocabTagFromStructure(
                    $current_element
                );
                if ($tag->getConditionPath()) {
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
                        $selects[$vocab->conditionValue()] = $this->factory
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
                        $groups[$value] = $this->factory->group(
                            isset($selects[$value]) ? [$selects[$value]] : [],
                            $this->presenter->getVocabValue($value)
                        );
                    }
                    $res = $this->factory->switchableGroup(
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
                } else {
                    $res = $this->factory->select(
                        'placeholder',
                        array_combine(
                            $tag->getVocabularies()[0]->values(),
                            $this->presenter->getVocab($tag->getVocabularies()[0])
                        )
                    );
                }
                break;

            case ilMDLOMDataFactory::TYPE_VOCAB_SOURCE:
                $res = $this->factory->hidden();
                break;

            case ilMDLOMDataFactory::TYPE_NON_NEG_INT:
                $res = $this->factory
                    ->numeric('placeholder')
                    ->withAdditionalTransformation(
                        $this->refinery->int()->isGreaterThanOrEqual(0)
                    )
                    ->withAdditionalTransformation(
                        $this->refinery->byTrying([
                            $this->refinery->kindlyTo()->string(),
                            $this->refinery->kindlyTo()->null()
                        ])
                    );
                break;

            case ilMDLOMDataFactory::TYPE_DATETIME:
                $res = $this->factory
                    ->dateTime('placeholder')
                    ->withFormat($this->presenter->getUserDateFormat())
                    ->withAdditionalTransformation(
                        $this->refinery->custom()->transformation(
                            function ($v) {
                                return (string) $v?->format('Y-m-d');
                            }
                        )
                    );
                break;

            case ilMDLOMDataFactory::TYPE_DURATION:
                $num = $this->factory
                    ->numeric('placeholder')
                    ->withAdditionalTransformation(
                        $this->refinery->int()->isGreaterThanOrEqual(0)
                    );
                $nums = [];
                foreach ($this->presenter->getDurationLabels() as $label) {
                    $nums[] = (clone $num)->withLabel($label);
                }
                $res = $this->factory->group($nums)->withAdditionalTransformation(
                    $this->refinery->custom()->transformation(function ($vs) {
                        if (
                            count(array_unique($vs)) === 1 &&
                            array_unique($vs)[0] === null
                        ) {
                            return '';
                        }
                        $r = 'P';
                        $signifiers = ['Y', 'M', 'D', 'H', 'M', 'S'];
                        foreach ($vs as $key => $int) {
                            if (isset($int)) {
                                $r .= $int . $signifiers[$key];
                            }
                            if (
                                $key === 2 &&
                                !isset($vs[3]) &&
                                !isset($vs[4]) &&
                                !isset($vs[5])
                            ) {
                                return $r;
                            }
                            if ($key === 2) {
                                $r .= 'T';
                            }
                        }
                        return $r;
                    })
                );
                $default = ['', '', '', '', '', ''];
                break;

            default:
                throw new ilMDEditorException(
                    'Invalid data type ' . $type
                );
        }

        $skip_initial = !$this->getElementUITagFromStructure(
            $current_element
        )?->isLabelImportant();

        $res = $res
            ->withValue(
                $current_element->isScaffold() ?
                    $default : $this->getDataValueForInput(
                        $current_element->getData(),
                        $this->presenter
                    )
            )
            ->withLabel(
                $this->presenter->getElementNameWithParents(
                    $current_element,
                    false,
                    $action_element->getName(),
                    $skip_initial
                )
            );

        $res_path = $this->appendPath(
            $path,
            $element,
            $current_element
        );
        $current_index = $this->getIndexOfElement(
            $root,
            $res_path
        );
        $presets = $constraint_tag?->getPresetValues() ?? [];
        if (key_exists($current_index, $presets)) {
            $res = $res->withValue($presets[$current_index]);
        }
        $not_deletables = $constraint_tag?->getIndicesNotDeletable() ?? [];
        if (in_array($current_index, $not_deletables)) {
            $res = $res->withRequired(true);
        }
        $not_editables = $constraint_tag?->getIndicesNotEditable() ?? [];
        if (in_array($current_index, $not_editables)) {
            $res = $res->withDisabled(true);
        }

        return ['input' => $res, 'path' => $res_path];
    }

    /**
     * @param ilMDData  $data
     * @param ilObjUser $user
     * @return string|string[]
     */
    protected function getDataValueForInput(
        ilMDData $data,
        ilMDLOMPresenter $presenter
    ): string|array {
        switch ($data->getType()) {
            case ilMDLOMDataFactory::TYPE_DATETIME:
                preg_match(
                    ilMDLOMDataFactory::DATETIME_REGEX,
                    $data->getValue(),
                    $matches,
                    PREG_UNMATCHED_AS_NULL
                );
                $date = new DateTimeImmutable(
                    ($matches[1] ?? '0000') . '-' .
                    ($matches[2] ?? '01') . '-' .
                    ($matches[3] ?? '01')
                );
                return $presenter->getUserDateFormat()->applyTo($date);

            case ilMDLOMDataFactory::TYPE_DURATION:
                preg_match(
                    ilMDLOMDataFactory::DURATION_REGEX,
                    $data->getValue(),
                    $matches,
                    PREG_UNMATCHED_AS_NULL
                );
                return array_slice($matches, 1);

            case ilMDLOMDataFactory::TYPE_VOCAB_VALUE:
                return $this->getDataValueForVocab($data->getValue());

            default:
                return $data->getValue();
        }
    }

    protected function getConditionElement(
        ilMDBaseElement $start_element,
        ilMDPathRelative $path
    ): ilMDBaseElement {
        for ($i = 1; $i < $path->getPathLength(); $i++) {
            $step = $path->getStep($i);
            if ($step === ilMDPath::SUPER_ELEMENT) {
                $start_element = $start_element->getSuperElement();
                continue;
            }
            $els = $start_element->getSubElements($step);
            if (count($els) > 1) {
                throw new ilMDRepositoryException(
                    'Path to condition element of ' .
                    $start_element->getName() . ' is not unique.'
                );
            }
            if (count($els) === 0) {
                throw new ilMDRepositoryException(
                    'Path to condition element of ' .
                    $start_element->getName() . ' does not lead to an element.'
                );
            }
            $start_element = $els[0];
        }
        return $start_element;
    }

    protected function getDataValueForVocab(string $value): string
    {
        $value = strtolower(
            preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $value)
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

    /**
     * If the supplied path leads to multiple elements,
     * it takes the first scaffold.
     */
    protected function getUniqueElement(
        ilMDRootElement $root,
        ilMDPathFromRoot $path
    ): ilMDBaseElement {
        $els = $root->getSubElementsByPath($path, null, true);
        if (count($els) < 1) {
            throw new ilMDEditorException(
                'The path does not lead to an element.'
            );
        }
        if (count($els) > 1) {
            throw new ilMDEditorException(
                'The path does not lead to a unique element.'
            );
        }
        return $els[0];
    }
}
