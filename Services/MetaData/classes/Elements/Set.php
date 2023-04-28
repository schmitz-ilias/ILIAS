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

namespace ILIAS\MetaData\Elements;

use ILIAS\MetaData\Elements\Base\BaseSet;
use ILIAS\MetaData\Elements\RessourceID\RessourceID;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Set extends BaseSet implements SetInterface
{
    private RessourceID $ressource_id;

    public function __construct(
        RessourceID $ressource_id,
        Element $root
    ) {
        parent::__construct($root);
        $this->ressource_id = $ressource_id;
    }

    public function getRessourceID(): RessourceID
    {
        return $this->ressource_id;
    }

    public function getRoot(): Element
    {
        $root = parent::getRoot();
        if ($root instanceof Element) {
            return $root;
        }
        throw new \ilMDElementsException(
            'Metadata set has invalid root element.'
        );
    }
}
