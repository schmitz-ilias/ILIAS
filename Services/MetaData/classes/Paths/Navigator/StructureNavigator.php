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

namespace ILIAS\MetaData\Paths\Navigator;

use ILIAS\MetaData\Elements\Structure\StructureElementInterface;
use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\MetaData\Paths\Steps\NavigatorBridge;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class StructureNavigator extends BaseNavigator implements StructureNavigatorInterface
{
    public function __construct(
        PathInterface $path,
        StructureElementInterface $start_element,
        NavigatorBridge $bridge
    ) {
        parent::__construct($path, $start_element, $bridge);
        $this->leadsToOne(true);
    }

    public function nextStep(): ?NavigatorInterface
    {
        $return = parent::nextStep();
        if (($return instanceof NavigatorInterface) || is_null($return)) {
            return $return;
        }
        throw new \ilMDPathException('Invalid Navigator');
    }

    /**
     * @return StructureElementInterface[]
     * @throws \ilMDPathException
     */
    public function lastElements(): \Generator
    {
        foreach (parent::lastElements() as $element) {
            if (!($element instanceof StructureElementInterface)) {
                throw new \ilMDElementsException(
                    'Invalid Navigator.'
                );
            }
            yield $element;
        }
    }

    /**
     * @return StructureElementInterface[]
     * @throws \ilMDPathException
     */
    public function elements(): \Generator
    {
        foreach (parent::lastElements() as $element) {
            if (!($element instanceof StructureElementInterface)) {
                throw new \ilMDElementsException(
                    'Invalid Navigator.'
                );
            }
            yield $element;
        }
    }
}
