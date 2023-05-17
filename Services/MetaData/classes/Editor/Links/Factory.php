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

namespace ILIAS\MetaData\Editor\Links;

use ILIAS\Data\Factory as DataFactory;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class Factory implements FactoryInterface
{
    protected \ilCtrlInterface $ctrl;
    protected DataFactory $data_factory;

    /**
     * @var string[]
     */
    protected array $parameters = [];
    protected Command $command;

    public function __construct(
        \ilCtrlInterface $ctrl,
        DataFactory $data_factory
    ) {
        $this->ctrl = $ctrl;
        $this->data_factory = $data_factory;
    }

    public function custom(Command $command): BuilderInterface
    {
        return new Builder($this->ctrl, $this->data_factory, $command);
    }
}
