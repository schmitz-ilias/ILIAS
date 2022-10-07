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

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDPathFactory
{
    public function getPathFromRoot(): ilMDPathFromRoot
    {
        return new ilMDPathFromRoot();
    }

    public function getRelativePath(string $start): ilMDPathRelative
    {
        return new ilMDPathRelative($start);
    }

    public function getStructurePointerAsPath(
        ilMDStructure $structure
    ): ilMDPathFromRoot {
        $path = new ilMDPathFromRoot();
        $structure = clone $structure;
        $pointer = [];
        while (!$structure->isPointerAtRootElement()) {
            array_unshift($pointer, $structure->getNameAtPointer());
            $structure->movePointerToSuperElement();
        }
        foreach ($pointer as $key) {
            $path->addStep($key);
        }
        unset($structure);
        return $path;
    }
}
