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

use ILIAS\MetaData\Elements\ElementInterface;
use ILIAS\MetaData\Paths\Steps\NavigatorBridge;
use ILIAS\MetaData\Paths\PathInterface;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Navigator extends BaseNavigator implements NavigatorInterface
{
    public function __construct(
        PathInterface $path,
        ElementInterface $start_element,
        NavigatorBridge $bridge
    ) {
        parent::__construct($path, $start_element, $bridge);
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
     * @return ElementInterface[]
     * @throws \ilMDPathException
     */
    public function elementsAtLastStep(): \Generator
    {
        foreach (parent::elementsAtLastStep() as $element) {
            if (!($element instanceof ElementInterface)) {
                throw new \ilMDElementsException(
                    'Invalid Navigator.'
                );
            }
            yield $element;
        }
    }

    /**
     * @throws \ilMDPathException
     */
    public function firstElementAtLastStep(): ?ElementInterface
    {
        return $this->elementsAtLastStep()->current();
    }

    /**
     * @return ElementInterface[]
     * @throws \ilMDPathException
     */
    public function elements(): \Generator
    {
        foreach (parent::elements() as $element) {
            if (!($element instanceof ElementInterface)) {
                throw new \ilMDElementsException(
                    'Invalid Navigator.'
                );
            }
            yield $element;
        }
    }


    /**
     * @throws \ilMDPathException
     */
    public function firstElement(): ?ElementInterface
    {
        return $this->elementsAtLastStep()->current();
    }
}
