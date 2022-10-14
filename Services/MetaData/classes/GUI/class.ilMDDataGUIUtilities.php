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
use ILIAS\UI\Component\Input\Field\Factory;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\DateFormat\DateFormat;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDDataGUIUtilities
{
    protected const COND_VALUE = 'md_cond_value';
    protected const VALUE = 'md_value';

    /**
     * @return ilMDBaseElement[]
     */
    public function getDataElements(
        ilMDBaseElement $start_element,
        ilMDLOMStructure $structure
    ): array {
        $elements = [];
        $this->addDataElements($elements, $start_element, $structure, 0);
        return $elements;
    }

    /**
     * @param ilMDBaseElement[] $elements
     * @param ilMDBaseElement   $current_element
     * @param ilMDLOMStructure  $structure
     * @param int               $depth
     */
    protected function addDataElements(
        array &$elements,
        ilMDBaseElement $current_element,
        ilMDLOMStructure $structure,
        int $depth
    ): void {
        //stop the recursion after a while, just to be safe.
        if ($depth >= 20) {
            throw new ilMDGUIException(
                'Recursion reached its maximum depth'
            );
        }

        $type = $this->getElementDataTypeFromStructure(
            $current_element,
            $structure
        );
        if ($type !== ilMDLOMDataFactory::TYPE_NONE) {
            $elements[] = $current_element;
        }
        foreach ($current_element->getSubElements() as $sub) {
            $this->addDataElements(
                $elements,
                $sub,
                $structure->movePointerToRoot(),
                $depth + 1
            );
        }
    }

    public function getInputSection(
        ilMDRootElement $root,
        ilMDPathFromRoot $path,
        ilMDLOMVocabulariesStructure $structure,
        Factory $factory,
        Refinery $refinery,
        ilMDLOMPresenter $presenter,
        ilObjUser $user,
        DataFactory $data
    ): Section {
        if (empty($els = $root->getSubElementsByPath($path))) {
            throw new ilMDGUIException(
                'The path to the current' .
                ' element does not lead to an element.'
            );
        }
        if (count($els) > 1) {
            throw new ilMDGUIException(
                'The path to the current' .
                ' element leads to multiple elements.'
            );
        }
        $element = $els[0];
        $data_els = $this->getDataElements($element, $structure);
        $inputs = [];
        foreach ($data_els as $data_el) {
            $post_path = $this->appendPath(
                $path,
                $element,
                $data_el
            );
            $inputs[$post_path->getPathAsString()] = $this->getInputForElement(
                $element,
                $path,
                $data_el,
                $structure,
                $factory,
                $refinery,
                $presenter,
                $user,
                $data
            );
        }
        return $factory->section(
            $inputs,
            $presenter->getElementNameWithParents($element, false)
        )->withAdditionalTransformation(
            $refinery->custom()->transformation(function ($vs) {
                foreach ($vs as $key => $v) {
                    if (is_array($v)) {
                        $vs[$key] = $v[self::VALUE];
                        $vs[$v[self::COND_VALUE][0]] = $v[self::COND_VALUE][1];
                    }
                }
            })
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
            $steps[] = $new_path_end->getName();
            $new_path_end = $new_path_end->getSuperElement();
        }
        foreach ($steps as $step) {
            $res->addStep($step);
        }
        return $res;
    }

    protected function getElementDataTypeFromStructure(
        ilMDBaseElement $element,
        ilMDLOMStructure $structure,
    ): string {
        $name_path = [];
        while (!($element instanceof ilMDRootElement)) {
            array_unshift($name_path, $element->getName());
            $element = $element->getSuperElement();
        }
        $structure->movePointerToRoot();
        foreach ($name_path as $next_name) {
            $structure->movePointerToSubElement($next_name);
        }
        return $structure->getTypeAtPointer();
    }

    protected function getElementVocabTagFromStructure(
        ilMDBaseElement $element,
        ilMDLOMVocabulariesStructure $structure,
    ): ?ilMDVocabulariesTag {
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

    protected function getInputForElement(
        ilMDBaseElement $element,
        ilMDPathFromRoot $path,
        ilMDBaseElement $current_element,
        ilMDLOMVocabulariesStructure $structure,
        Factory $factory,
        Refinery $refinery,
        ilMDLOMPresenter $presenter,
        ilObjUser $user,
        DataFactory $data
    ): FormInput {
        $type = $this->getElementDataTypeFromStructure(
            $current_element,
            $structure
        );

        switch ($type) {
            case ilMDLOMDataFactory::TYPE_NONE:
                throw new ilMDGUIException(
                    'Cannot generate input field for element with no data.'
                );

            case ilMDLOMDataFactory::TYPE_STRING:
                if ($element->getName() === 'description') {
                    $res = $factory->textarea('placeholder');
                } else {
                    $res = $factory->text('placeholder');
                }
                break;

            case ilMDLOMDataFactory::TYPE_LANG:
                $res = $factory->select(
                    'placeholder',
                    array_combine(
                        ilMDLOMDataFactory::LANGUAGES,
                        $presenter->getLanguages()
                    )
                );
                break;

            case ilMDLOMDataFactory::TYPE_VOCAB_VALUE:
                $tag = $this->getElementVocabTagFromStructure(
                    $current_element,
                    $structure
                );
                if ($tag->getConditionPath()) {
                    $selects = [];
                    foreach ($tag->getVocabularies() as $vocab) {
                        $v = $current_element->isScaffold() ?
                            '' : $this->getDataValueForInput(
                                $current_element->getData(),
                                $user
                            );
                        $v = in_array($v, $vocab->getValues()) ? $v : '';
                        $selects[$vocab->getConditionValue()] = $factory
                            ->select(
                                $presenter->getElementNameWithParents(
                                    $current_element,
                                    false,
                                    $element->getName()
                                ),
                                array_combine(
                                    $vocab->getValues(),
                                    $presenter->getVocab($vocab)
                                )
                            )->withValue($v);
                    }
                    $cond_el = $this->getConditionElement(
                        $current_element,
                        $tag->getConditionPath()
                    );
                    $absolute_cond_path = $this->appendPath(
                        $path,
                        $element,
                        $cond_el
                    )->getPathAsString();
                    $cond_tag = $this->getElementVocabTagFromStructure(
                        $cond_el,
                        $structure
                    );
                    $vocab = $cond_tag->getVocabularies()[0];
                    $groups = [];
                    foreach ($vocab->getValues() as $value) {
                        $groups[$value] = $factory->group(
                            isset($selects[$value]) ? [$selects[$value]] : [],
                            $presenter->getVocabValue($value)
                        );
                    }
                    $res = $factory->switchableGroup(
                        $groups,
                        'placeholder'
                    )->withAdditionalTransformation(
                        $refinery->custom()->transformation(
                            function ($vs) use ($absolute_cond_path) {
                                $r[self::COND_VALUE] = [
                                    $absolute_cond_path,
                                    $vs[0]
                                ];
                                $r[self::VALUE] = $vs[1][0];
                                return $r;
                            }
                        )
                    );
                    $current_element = $cond_el;
                } else {
                    $res = $factory->select(
                        'placeholder',
                        array_combine(
                            $tag->getVocabularies()[0]->getValues(),
                            $presenter->getVocab($tag->getVocabularies()[0])
                        )
                    );
                }
                break;

            case ilMDLOMDataFactory::TYPE_VOCAB_SOURCE:
                $res = $factory->hidden();
                break;

            case ilMDLOMDataFactory::TYPE_NON_NEG_INT:
                $res = $factory
                    ->numeric('placeholder')
                    ->withAdditionalTransformation(
                        $refinery->int()->isGreaterThan(0)
                    );
                break;

            case ilMDLOMDataFactory::TYPE_DATETIME:
                $res = $factory
                    ->dateTime('placeholder')
                    ->withFormat($this->getUserFormat($user, $data));
                break;

            case ilMDLOMDataFactory::TYPE_DURATION:
                $num = $factory
                    ->numeric('placeholder')
                    ->withAdditionalTransformation(
                        $refinery->int()->isGreaterThan(0)
                    );
                $nums = [];
                foreach ($presenter->getDurationLabels() as $label) {
                    $nums[] = (clone $num)->withLabel($label);
                }
                $res = $factory->group($nums)->withAdditionalTransformation(
                    $refinery->custom()->transformation(function ($vs) {
                        if (
                            count(array_unique($vs)) &&
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
                            if ($key === 2 && count($vs) < 4) {
                                return $r;
                            }
                            if ($key === 2) {
                                $r .= 'T';
                            }
                        }
                        return $r;
                    })
                );
                break;

            default:
                throw new ilMDGUIException(
                    'Invalid data type ' . $type
                );
        }

        return $res
            ->withValue(
                $current_element->isScaffold() ?
                    '' : $this->getDataValueForInput(
                        $current_element->getData(),
                        $user
                    )
            )
            ->withLabel(
                $presenter->getElementNameWithParents(
                    $current_element,
                    false,
                    $element->getName()
                )
            );
    }

    /**
     * @param ilMDData  $data
     * @param ilObjUser $user
     * @return string|string[]
     */
    public function getDataValueForInput(
        ilMDData $data,
        ilObjUser $user
    ): string|array {
        switch ($data->getType()) {
            case ilMDLOMDataFactory::TYPE_DATETIME:
                preg_match(
                    ilMDLOMDataFactory::DATETIME_REGEX,
                    $data->getValue(),
                    $matches,
                    PREG_UNMATCHED_AS_NULL
                );
                $date = new ilDate(
                    $matches[1] . '-' . $matches[2] . '-' . $matches[3],
                    IL_CAL_DATE
                );
                return $date->get(
                    IL_CAL_FKT_GETDATE,
                    $user->getDateFormat()
                );

            case ilMDLOMDataFactory::TYPE_DURATION:
                preg_match(
                    ilMDLOMDataFactory::DURATION_REGEX,
                    $data->getValue(),
                    $matches,
                    PREG_UNMATCHED_AS_NULL
                );
                $res_array = [];
                foreach (array_slice($matches, 1) as $key => $match) {
                    if ($match) {
                        $res_array[] = $match;
                    }
                }
                return $res_array;

            case ilMDLOMDataFactory::TYPE_VOCAB_VALUE:
                return strtolower($data->getValue());

            default:
                return $data->getValue();
        }
    }

    protected function getUserFormat(
        ilObjUser $user,
        DataFactory $data
    ): DateFormat {
        $array = explode('', $user->getDateFormat());
        $builder = $data->dateFormat()->custom();
        foreach ($array as $char) {
            switch ($char) {
                case '.':
                    $builder->dot();
                    break;

                case '-':
                    $builder->dash();
                    break;

                case '/':
                    $builder->slash();
                    break;

                case 'Y':
                    $builder->year();
                    break;

                case 'm':
                    $builder->month();
                    break;

                case 'd':
                    $builder->day();
                    break;

                default:
                    throw new ilMDGUIException(
                        'Date format conversion failed'
                    );
            }
        }
        return $builder->get();
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
                throw new ilMDDatabaseException(
                    'Path to condition element of ' .
                    $start_element->getName() . ' is not unique.'
                );
            }
            if (count($els) === 0) {
                throw new ilMDDatabaseException(
                    'Path to condition element of ' .
                    $start_element->getName() . ' does not lead to an element.'
                );
            }
            $start_element = $els[0];
        }
        return $start_element;
    }
}
