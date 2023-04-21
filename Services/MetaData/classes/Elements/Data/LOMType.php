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

namespace ILIAS\MetaData\Elements\Data;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
enum LOMType: string
{
    case TYPE_NULL = 'none';
    case TYPE_STRING = 'string';
    case TYPE_LANG = 'lang';
    case TYPE_VOCAB_SOURCE = 'vocab_source';
    case TYPE_VOCAB_VALUE = 'vocab_value';
    case TYPE_DATETIME = 'datetime';
    case TYPE_NON_NEG_INT = 'non_neg_int';
    case TYPE_DURATION = 'duration';
}
