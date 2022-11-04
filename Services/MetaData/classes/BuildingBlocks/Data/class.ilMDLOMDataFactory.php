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
class ilMDLOMDataFactory
{
    public const TYPE_NONE = 'none';
    public const TYPE_STRING = 'string';
    public const TYPE_LANG = 'lang';
    public const TYPE_VOCAB_SOURCE = 'vocab_source';
    public const TYPE_VOCAB_VALUE = 'vocab_value';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_NON_NEG_INT = 'non_neg_int';
    public const TYPE_DURATION = 'duration';

    // Note that 'xx' should be translated to 'none'
    public const LANGUAGES = [
        "aa", "ab", "af", "am", "ar", "as", "ay", "az", "ba", "be", "bg", "bh",
        "bi", "bn", "bo", "br", "ca", "co", "cs", "cy", "da", "de", "dz", "el",
        "en", "eo", "es", "et", "eu", "fa", "fi", "fj", "fo", "fr", "fy", "ga",
        "gd", "gl", "gn", "gu", "ha", "he", "hi", "hr", "hu", "hy", "ia", "ie",
        "ik", "id", "is", "it", "iu", "ja", "jv", "ka", "kk", "kl", "km", "kn",
        "ko", "ks", "ku", "ky", "la", "ln", "lo", "lt", "lv", "mg", "mi", "mk",
        "ml", "mn", "mo", "mr", "ms", "mt", "my", "na", "ne", "nl", "no", "oc",
        "om", "or", "pa", "pl", "ps", "pt", "qu", "rm", "rn", "ro", "ru", "rw",
        "sa", "sd", "sg", "sh", "si", "sk", "sl", "sm", "sn", "so", "sq", "sr",
        "ss", "st", "su", "sv", "sw", "ta", "te", "tg", "th", "ti", "tk", "tl",
        "tn", "to", "tr", "ts", "tt", "tw", "ug", "uk", "ur", "uz", "vi", "vo",
        "wo", "xh", "yi", "yo", "za", "zh", "zu", "xx"
    ];

    /**
     * This monstrosity makes sure datetimes conform to the format given by LOM,
     * and picks out the relevant numbers.
     * match 1: YYYY, 2: MM, 3: DD, 4: hh, 5: mm, 6: ss, 7: s (arbitrary many
     * digits for decimal fractions of seconds), 8: timezone, either Z for
     * UTC or +- hh:mm (mm is optional)
     */
    public const DATETIME_REGEX = '/^(\d{4})(?:-(\d{2})(?:-(\d{2})' .
    '(?:T(\d{2})(?::(\d{2})(?::(\d{2})(?:\.(\d+)(Z|[+\-]' .
    '\d{2}(?::\d{2})?)?)?)?)?)?)?)?$/';

    /**
     * This monstrosity makes sure durations conform to the format given by LOM,
     * and picks out the relevant numbers.
     * match 1: years, 2: months, 3: days, 4: hours, 5: minutes, 6: seconds
     */
    public const DURATION_REGEX = '/^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)' .
    '?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)(?:.\d+)?S)?)?$/';

    protected ilMDLOMDataConstraintProvider $constraint;

    public function __construct(ilMDLOMDataConstraintProvider $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * @param string                $type
     * @param string                $value
     * @param ilMDVocabulary[]      $vocabularies
     * @param ilMDPathRelative|null $path_to_condition
     * @return ilMDData
     */
    protected function MDData(
        string $type,
        string $value,
        array $vocabularies = [],
        ?ilMDPathRelative $path_to_condition = null
    ): ilMDData {
        if (
            ($type === self::TYPE_VOCAB_SOURCE ||
            $type === self::TYPE_VOCAB_VALUE) &&
            empty($vocabularies)
        ) {
            throw new ilMDBuildingBlocksException(
                "Vocabulary data can not be constructed without vocabularies."
            );
        }
        if (
            !($type === self::TYPE_VOCAB_VALUE) &&
            isset($path_to_condition)
        ) {
            throw new ilMDBuildingBlocksException(
                "Only vocabulary values can be conditional on other elements."
            );
        }
        return new ilMDData(
            $type,
            $value,
            $this->constraint->byType(
                $type,
                $vocabularies,
                isset($path_to_condition)
            ),
            $path_to_condition
        );
    }

    public function byPath(
        string $value,
        ilMDPathFromRoot $path,
        ilMDLOMVocabulariesDictionary $vocab_dict
    ): ilMDData {
        $structure = $vocab_dict->getStructure();
        return $this->MDData(
            $structure
                ->movePointerToEndOfPath($path)
                ->getTypeAtPointer(),
            $value,
            $structure
                ->getTagAtPointer()
                ?->getVocabularies() ?? [],
            $structure
                ->getTagAtPointer()
                ?->getConditionPath()
        );
    }

    public function none(): ilMDData
    {
        return $this->MDData(self::TYPE_NONE, '');
    }

    public function string(string $value): ilMDData
    {
        return $this->MDData(self::TYPE_STRING, $value);
    }

    public function language(string $value): ilMDData
    {
        return $this->MDData(self::TYPE_LANG, $value);
    }

    /**
     * @param string            $value
     * @param ilMDVocabulary[]  $vocabularies
     * @return ilMDData
     */
    public function vocabSource(
        string $value,
        array $vocabularies
    ): ilMDData {
        return $this->MDData(self::TYPE_VOCAB_SOURCE, $value, $vocabularies);
    }

    /**
     * @param string            $value
     * @param ilMDVocabulary[]  $vocabularies
     * @return ilMDData
     */
    public function vocabValue(
        string $value,
        array $vocabularies
    ): ilMDData {
        return $this->MDData(self::TYPE_VOCAB_VALUE, $value, $vocabularies);
    }

    /**
     * @param string            $value
     * @param ilMDVocabulary[]  $vocabularies
     * @param ilMDPathRelative  $path_to_condition
     * @return ilMDData
     */
    public function conditionalVocabValue(
        string $value,
        array $vocabularies,
        ilMDPathRelative $path_to_condition
    ): ilMDData {
        return $this->MDData(
            self::TYPE_VOCAB_VALUE,
            $value,
            $vocabularies,
            $path_to_condition
        );
    }

    public function datetime(string $value): ilMDData
    {
        return $this->MDData(self::TYPE_DATETIME, $value);
    }

    public function nonNegativeInt(string $value): ilMDData
    {
        return $this->MDData(self::TYPE_NON_NEG_INT, $value);
    }

    public function duration(string $value): ilMDData
    {
        return $this->MDData(self::TYPE_DURATION, $value);
    }
}
