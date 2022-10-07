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
class ilMDTagFactory
{
    /**
     * @param string   $create
     * @param string   $read
     * @param string   $update
     * @param string   $delete
     * @param string   $table
     * @param string[] $expected_params
     * @return ilMDDatabaseTag
     */
    public function databaseTag(
        string $create,
        string $read,
        string $update,
        string $delete,
        string $table,
        array $expected_params = []
    ): ilMDDatabaseTag {
        return new ilMDDatabaseTag(
            $create,
            $read,
            $update,
            $delete,
            $table,
            $expected_params
        );
    }

    public function vocabulariesTag(): ilMDVocabulariesTagBuilder
    {
        return new ilMDVocabulariesTagBuilder();
    }

    public function editorGUITag(
        ?ilMDPathRelative $path_to_preview = null,
        ?ilMDPathRelative $path_to_representation = null
    ): ilMDEditorGUITag {
        return new ilMDEditorGUITag(
            $path_to_preview,
            $path_to_representation
        );
    }
}
