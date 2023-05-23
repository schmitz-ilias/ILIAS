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

namespace ILIAS\MetaData\Editor\Http;

use ILIAS\MetaData\Paths\PathInterface;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\UI\Component\Modal\RoundTrip as RoundtripModal;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
interface RequestForFormInterface
{
    public function path(): ?PathInterface;

    public function applyRequestToForm(StandardForm $form): StandardForm;

    public function applyRequestToModal(RoundtripModal $modal): RoundtripModal;
}
