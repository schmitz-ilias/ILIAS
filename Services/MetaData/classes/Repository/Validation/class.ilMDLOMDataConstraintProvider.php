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

namespace Validation;

use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\Factory;
use classes\Vocabularies\ilMDVocabulary;
use ilMDBuildingBlocksException;
use classes\Elements\Data\ilMDLOMDataFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDataConstraintProvider
{
    protected Factory $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string           $type
     * @param ilMDVocabulary[] $vocabularies
     * @param bool             $conditional
     * @return Constraint
     */
    public function byType(
        string $type,
        array $vocabularies,
        bool $conditional
    ): Constraint {
        if ($conditional) {
            if ($type !== ilMDLOMDataFactory::TYPE_VOCAB_VALUE) {
                throw new ilMDBuildingBlocksException(
                    "Only vocabulary values can have a conditional constraint."
                );
            }
            return $this->conditionalVocabValue($vocabularies);
        }

        switch ($type) {
            case(ilMDLOMDataFactory::TYPE_NULL):
                return $this->null();

            case(ilMDLOMDataFactory::TYPE_STRING):
                return $this->string();

            case(ilMDLOMDataFactory::TYPE_LANG):
                return $this->language();

            case(ilMDLOMDataFactory::TYPE_VOCAB_SOURCE):
                return $this->vocabSource($vocabularies);

            case(ilMDLOMDataFactory::TYPE_VOCAB_VALUE):
                return $this->vocabValue($vocabularies);

            case(ilMDLOMDataFactory::TYPE_DATETIME):
                return $this->datetime();

            case(ilMDLOMDataFactory::TYPE_NON_NEG_INT):
                return $this->nonNegativeInt();

            case(ilMDLOMDataFactory::TYPE_DURATION):
                return $this->duration();

            default:
                throw new ilMDBuildingBlocksException("Invalid MD data type.");
        }
    }

    /**
     * @param ilMDVocabulary[] $vocabularies
     */
    protected function conditionalVocabValue(
        array $vocabularies
    ): Constraint {
        $values = [];
        $values[''] = [];
        foreach ($vocabularies as $vocabulary) {
            if (!$vocabulary->conditionValue()) {
                $values[''] = array_merge(
                    $values[''],
                    $vocabulary->values()
                );
                continue;
            }
            $key = $vocabulary->conditionValue();
            if (array_key_exists($key, $values)) {
                $values[$key] = array_merge(
                    $values[$key],
                    $vocabulary->values()
                );
                continue;
            }
            $values[$key] = $vocabulary->values();
        }
        return $this->factory->custom()->constraint(
            /*
             * args[0] should be the regular value,
             * and args[1] the condition value.
             */
            function (array $args) use ($values) {
                return in_array(
                    str_replace(' ', '', strtolower($args[0])),
                    array_map(
                        fn (string $s) => str_replace(' ', '', strtolower($s)),
                        $values[$args[1]] ?? $values['']
                    )
                );
            },
            'Invalid vocabulary value'
        );
    }

    protected function null(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
                return $arg === '';
            },
            'There should not be any data here.'
        );
    }

    protected function string(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
                return $arg !== '';
            },
            'This should not be empty.'
        );
    }

    protected function language(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
                return in_array($arg, ilMDLOMDataFactory::LANGUAGES);
            },
            'Invalid language'
        );
    }

    /**
     * @param ilMDVocabulary[] $vocabularies
     */
    protected function vocabSource(
        array $vocabularies
    ): Constraint {
        $sources = [];
        foreach ($vocabularies as $vocabulary) {
            $sources[] = $vocabulary->source();
        }
        return $this->factory->custom()->constraint(
            function (string $arg) use ($sources) {
                return in_array($arg, $sources);
            },
            'Invalid vocabulary source'
        );
    }

    /**
     * @param ilMDVocabulary[] $vocabularies
     */
    protected function vocabValue(
        array $vocabularies
    ): Constraint {
        $values = [];
        foreach ($vocabularies as $vocabulary) {
            if ($vocabulary->conditionValue()) {
                continue;
            }
            $values = array_merge($values, $vocabulary->values());
        }
        return $this->factory->custom()->constraint(
            function (string $arg) use ($values) {
                return in_array(
                    str_replace(' ', '', strtolower($arg)),
                    array_map(
                        fn (string $s) => str_replace(
                            ' ',
                            '',
                            strtolower($s)
                        ),
                        $values
                    )
                );
            },
            'Invalid vocabulary value'
        );
    }

    protected function datetime(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
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
    }

    protected function nonNegativeInt(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
                return (bool) preg_match('/^\d+$/', $arg);
            },
            'Invalid non-negative integer'
        );
    }

    protected function duration(): Constraint
    {
        return $this->factory->custom()->constraint(
            function (string $arg) {
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
                    if (isset($match) && (int) $match < 0) {
                        return false;
                    }
                }
                return true;
            },
            'Invalid LOM duration'
        );
    }
}
