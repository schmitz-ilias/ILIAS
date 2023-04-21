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

namespace Validation\Types;

use classes\Elements\Data\ilMDData;
use classes\Elements\Data\LOMDataType;
use classes\Vocabularies\ilMDVocabulary;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDVocabValueData extends ilMDData
{
    protected const ERROR = 'Invalid vocabulary value.';

    public function getType(): LOMDataType
    {
        return LOMDataType::TYPE_VOCAB_VALUE;
    }

    public function isValid(
        ?ilMDData $condition_data = null,
        ilMDVocabulary ...$vocabs
    ): bool {
        $cond_value = $condition_data?->getValue();
        $vocab_values = [];
        foreach ($vocabs as $vocabulary) {
            if (
                $vocabulary->isConditional() &&
                $vocabulary->conditionValue() !== $cond_value
            ) {
                continue;
            }
            $vocab_values = array_merge($vocab_values, $vocabulary->values());
        }
        return in_array(
            $this->normalize($this->getValue()),
            array_map(
                fn (string $s) => $this->normalize($s),
                $vocab_values
            )
        );
    }

    /**
     * This is done to ensure backwards compatibility.
     */
    protected function normalize(string $string): string
    {
        return str_replace(' ', '', strtolower($string));
    }
}
