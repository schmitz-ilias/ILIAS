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

use classes\Elements\Data\ilMDLOMDataFactory;
use classes\Elements\Data\ilMDData;
use classes\Elements\Data\LOMDataType;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDDurationData extends ilMDData
{
    protected const ERROR = 'Invalid LOM duration.';

    /**
     * This monstrosity makes sure durations conform to the format given by LOM,
     * and picks out the relevant numbers.
     * match 1: years, 2: months, 3: days, 4: hours, 5: minutes, 6: seconds
     */
    public const DURATION_REGEX = '/^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)' .
    '?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)(?:.\d+)?S)?)?$/';

    public function getType(): LOMDataType
    {
        return LOMDataType::TYPE_DURATION;
    }

    public function isValid(): bool
    {
        if (!preg_match(
            ilMDLOMDataFactory::DURATION_REGEX,
            $this->getValue(),
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
    }
}
