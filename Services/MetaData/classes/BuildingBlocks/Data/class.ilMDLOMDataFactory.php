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

use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Factory;

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
        "wo", "xh", "yi", "yo", "za", "zh", "zu", "none"
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

    protected Factory $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function MDData(
        string $type,
        string $value,
        ?ilMDVocabulary $vocabulary = null
    ): ilMDData {
        if (
            ($type === self::TYPE_VOCAB_SOURCE ||
            $type === self::TYPE_VOCAB_VALUE) &&
            !isset($vocabulary)
        ) {
            throw new ilMDBuildingBlocksException(
                "Vocabulary data can not be constructed without a vocabulary."
            );
        }
        return new ilMDData(
            $type,
            $value,
            $this->getConstraintForType($type, $vocabulary)
        );
    }

    protected function getConstraintForType(
        string $type,
        ?ilMDVocabulary $vocabulary = null
    ): Constraint {
        switch ($type) {
            case(self::TYPE_NONE):
                throw new ilMDBuildingBlocksException(
                    'Can not create data of type none.'
                );

            case(self::TYPE_STRING):
                return $this->factory->custom()->constraint(
                    function (string $arg) {
                        return true;
                    },
                    ''
                );

            case(self::TYPE_LANG):
                return $this->factory->custom()->constraint(
                    function (string $arg) {
                        return in_array($arg, ilMDLOMDataFactory::LANGUAGES);
                    },
                    'Invalid language'
                );

            case(self::TYPE_VOCAB_SOURCE):
                return $this->factory->custom()->constraint(
                    function (string $arg) use ($vocabulary) {
                        return $arg === $vocabulary->getSource();
                    },
                    'Invalid vocabulary source'
                );

            case(self::TYPE_VOCAB_VALUE):
                return $this->factory->custom()->constraint(
                    function (string $arg) use ($vocabulary) {
                        return in_array($arg, $vocabulary->getValues());
                    },
                    'Invalid vocabulary value'
                );

            case(self::TYPE_DATETIME):
                return $this->factory->custom()->constraint(
                    function (string $arg) use ($vocabulary) {
                        if (!preg_match(
                            ilMDLOMDataFactory::DATETIME_REGEX,
                            $arg,
                            $matches,
                            PREG_UNMATCHED_AS_NULL
                        )) {
                            return false;
                        }
                        if (isset($matches[1]) && ((int) $matches[1]) < 1) {
                            return false;
                        }
                        if (isset($matches[2]) &&
                            (((int) $matches[2]) < 1 || ((int) $matches[2]) > 12)) {
                            return false;
                        }
                        if (isset($matches[3]) &&
                            (((int) $matches[3]) < 1 || ((int) $matches[3]) > 31)) {
                            return false;
                        }
                        if (isset($matches[4]) && ((int) $matches[4]) > 23) {
                            return false;
                        }
                        if (isset($matches[5]) && ((int) $matches[5]) > 59) {
                            return false;
                        }
                        if (isset($matches[6]) && ((int) $matches[6]) > 59) {
                            return false;
                        }
                        return true;
                    },
                    'Invalid LOM datetime'
                );

            case(self::TYPE_NON_NEG_INT):
                return $this->factory->custom()->constraint(
                    function (string $arg) {
                        return preg_match('/^\d+$/', $arg);
                    },
                    'Invalid non-negative integer'
                );

            case(self::TYPE_DURATION):
                return $this->factory->custom()->constraint(
                    function (string $arg) use ($vocabulary) {
                        if (!preg_match(
                            ilMDLOMDataFactory::DURATION_REGEX,
                            $arg,
                            $matches,
                            PREG_UNMATCHED_AS_NULL
                        )) {
                            return false;
                        }
                        unset($matches[0]);
                        foreach ($matches as $match) {
                            if (isset($match) && (int) $match <= 0) {
                                return false;
                            }
                        }
                        return true;
                    },
                    'Invalid LOM duration'
                );

            default:
                throw new ilMDBuildingBlocksException("Invalid MD data type.");
        }
    }
}
