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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMPresenter
{
    public const DELIMITER = ', ';
    public const SEPARATOR = ': ';

    protected ilLanguage $lng;
    protected ilObjUser $user;

    public function __construct(
        ilLanguage $lng,
        ilObjUser $user
    ) {
        $this->lng = $lng;
        $this->lng->loadLanguageModule('meta');
        $this->user = $user;
    }

    /**
     * @param ilMDBaseElement[]     $elements
     * @param ilMDPathRelative|null $path_to_representation
     * @param bool                  $plural
     * @return string
     */
    public function getElementsLabel(
        array $elements,
        ?ilMDPathRelative $path_to_representation = null,
        bool $plural = false
    ): string {
        $label = $this->getElementName($elements[0]->getName(), $plural);
        if (isset($path_to_representation) &&
            ($string = $this->getDataValueStringByPath(
                $elements,
                $path_to_representation
            ))
        ) {
            $label .= self::SEPARATOR . $string;
        }
        return $label;
    }

    /**
     * @param ilMDBaseElement[]     $elements
     * @param ilMDPathRelative|null $path_to_preview
     * @return string
     */
    public function getElementsPreview(
        array $elements,
        ?ilMDPathRelative $path_to_preview
    ): string {
        if (!isset($path_to_preview)) {
            return '';
        }
        return $this->getDataValueStringByPath($elements, $path_to_preview);
    }

    public function getElementName(
        string $name,
        bool $plural = false
    ): string {
        $exceptions = [
            'metadataSchema' => 'metadatascheme', 'lifeCycle' => 'lifecycle'
        ];
        if (array_key_exists($name, $exceptions)) {
            $name = $exceptions[$name];
        }

        $lang_key = 'meta_' . $this->camelCaseToSnakeCase($name);
        if ($plural) {
            $lang_key .= '_plural';
        }
        return $this->txt($lang_key);
    }

    public function getElementNameWithParents(
        ilMDBaseElement $element,
        bool $plural = false,
        string $parent_cutoff = ''
    ): string {
        $res = $this->getElementName($element->getName(), $plural);
        if ($element->getName() === $parent_cutoff) {
            return $res;
        }
        $element = $element->getSuperElement();
        while (!$element->isRoot()) {
            if ($element->getName() === $parent_cutoff) {
                break;
            }
            $res = $this->getElementName($element->getName()) .
                self::SEPARATOR . $res;
            $element = $element->getSuperElement();
        }
        return $res;
    }

    public function getDataValue(ilMDData $data): string
    {
        switch ($data->getType()) {
            case ilMDLOMDataFactory::TYPE_VOCAB_VALUE:
                return $this->getVocabValue($data->getValue());

            case ilMDLOMDataFactory::TYPE_LANG:
                return $this->getLanguage($data->getValue());

            case ilMDLOMDataFactory::TYPE_DATETIME:
                preg_match(
                    ilMDLOMDataFactory::DATETIME_REGEX,
                    $data->getValue(),
                    $matches,
                    PREG_UNMATCHED_AS_NULL
                );
                $date = new ilDate(
                    ($matches[1] ?? '0000') . '-' .
                    ($matches[2] ?? '01') . '-' .
                    ($matches[3] ?? '01'),
                    IL_CAL_DATE
                );
                return $date->get(
                    IL_CAL_FKT_DATE,
                    $this->getUserDateFormat()
                );

            case ilMDLOMDataFactory::TYPE_DURATION:
                preg_match(
                    ilMDLOMDataFactory::DURATION_REGEX,
                    $data->getValue(),
                    $matches,
                    PREG_UNMATCHED_AS_NULL
                );
                $labels = [
                    ['md_years', 'md_year'],
                    ['md_months', 'md_month'],
                    ['md_days', 'md_day'],
                    ['md_hours', 'md_hour'],
                    ['md_minutes', 'md_minute'],
                    ['md_seconds', 'md_second'],
                ];
                $res_array = [];
                foreach (array_slice($matches, 1) as $key => $match) {
                    if ($match) {
                        $res_array[] =
                            $match . ' ' .
                            ($match === '1' ?
                                $this->txt($labels[$key][1]) :
                                $this->txt($labels[$key][0]));
                    }
                }
                return implode(', ', $res_array);

            default:
                return $data->getValue();
        }
    }

    /**
     * @param ilMDVocabulary $vocab
     * @return string[]
     */
    public function getVocab(ilMDVocabulary $vocab): array
    {
        return array_map(
            fn (string $arg) => $this->getVocabValue($arg),
            $vocab->getValues()
        );
    }

    public function getVocabValue(string $value): string
    {
        $value = $this->camelCaseToSpaces($value);
        $exceptions = [
            'ispartof' => 'is_part_of', 'haspart' => 'has_part',
            'isversionof' => 'is_version_of', 'hasversion' => 'has_version',
            'isformatof' => 'is_format_of', 'hasformat' => 'has_format',
            'references' => 'references',
            'isreferencedby' => 'is_referenced_by',
            'isbasedon' => 'is_based_on', 'isbasisfor' => 'is_basis_for',
            'requires' => 'requires', 'isrequiredby' => 'is_required_by',
            'graphical designer' => 'graphicaldesigner',
            'technical implementer' => 'technicalimplementer',
            'content provider' => 'contentprovider',
            'technical validator' => 'technicalvalidator',
            'educational validator' => 'educationalvalidator',
            'script writer' => 'scriptwriter',
            'instructional designer' => 'instructionaldesigner',
            'subject matter expert' => 'subjectmatterexpert'
        ];
        if (array_key_exists($value, $exceptions)) {
            $value = $exceptions[$value];
        }

        return $this->txt('meta_' . $this->fillSpaces($value));
    }

    /**
     * @return string[]
     */
    public function getLanguages(): array
    {
        return array_map(
            fn (string $arg) => $this->txt('meta_l_' . $arg),
            ilMDLOMDataFactory::LANGUAGES
        );
    }

    public function getLanguage(string $language): string
    {
        return $this->txt('meta_l_' . $language);
    }

    protected function fillSpaces(string $string): string
    {
        $string = str_replace(' ', '_', $string);
        return strtolower($string);
    }

    protected function camelCaseToSnakeCase(string $string): string
    {
        $string = preg_replace('/(?<=[a-z])(?=[A-Z])/', '_', $string);
        return strtolower($string);
    }

    protected function camelCaseToSpaces(string $string): string
    {
        $string = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $string);
        return strtolower($string);
    }

    /**
     * Please not that this ignores all filters of a path.
     * @param ilMDBaseElement[] $elements
     * @param ilMDPathRelative  $path
     * @return string
     */
    protected function getDataValueStringByPath(
        array $elements,
        ilMDPathRelative $path
    ): string {
        for ($i = 1; $i < $path->getPathLength(); $i++) {
            $new_els = [];
            foreach ($elements as $el) {
                $step = $path->getStep($i);
                if ($step === ilMDPath::SUPER_ELEMENT) {
                    $new_els[] = $el->getSuperElement();
                    continue;
                }
                $new_els = array_merge($new_els, $el->getSubElements($step));
            }
            $elements = $new_els;
        }

        $res = [];
        foreach ($elements as $el) {
            if (!$el->isScaffold()) {
                $res[] = $this->getDataValue($el->getData());
            }
        }
        return implode(
            self::DELIMITER,
            array_filter(
                $res,
                fn (string $arg) => !is_null($arg) && $arg !== ''
            )
        );
    }

    /**
     * @return string[]
     */
    public function getDurationLabels(): array
    {
        return [
            $this->txt('md_years'),
            $this->txt('md_months'),
            $this->txt('md_days'),
            $this->txt('md_hours'),
            $this->txt('md_minutes'),
            $this->txt('md_seconds')
        ];
    }

    public function getUserDateFormat(): string
    {
        switch ($this->user->getDateFormat()) {
            case ilCalendarSettings::DATE_FORMAT_DMY:
                return 'd.m.Y';

            case ilCalendarSettings::DATE_FORMAT_MDY:
                return 'm/d/Y';

            case ilCalendarSettings::DATE_FORMAT_YMD:
            default:
                return 'Y-m-d';
        }
    }

    public function txt(string $key): string
    {
        return $this->lng->txt($key);
    }

    /**
     * @param string    $key
     * @param string[]  $values
     */
    public function txtFill(string $key, array $values): string
    {
        if ($this->lng->exists($key)) {
            return sprintf($this->lng->txt($key), ...$values);
        }
        return $key . ' ' . implode(',', $values);
    }
}
