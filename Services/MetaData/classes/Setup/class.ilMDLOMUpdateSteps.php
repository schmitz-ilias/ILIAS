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
 * @author  Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMUpdateSteps implements ilDatabaseUpdateSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;
    }

    /**
     * Add a column to the il_meta_general table to store the
     * 'Aggregation Level' element.
     */
    public function step_1(): void
    {
        if (!$this->db->tableColumnExists('il_meta_general', 'general_aggl')) {
            $this->db->addTableColumn(
                'il_meta_general',
                'general_aggl',
                [
                    'type' => ilDBConstants::T_TEXT,
                    'length' => 16,
                ]
            );
        }
    }

    /**
     * Add two columns to the il_meta_contribute table to store the
     * descrption of the date and its language.
     */
    public function step_2(): void
    {
        if (!$this->db->tableColumnExists('il_meta_contribute', 'c_date_descr')) {
            $this->db->addTableColumn(
                'il_meta_contribute',
                'c_date_descr',
                [
                    'type' => ilDBConstants::T_CLOB,
                ]
            );
        }
        if (!$this->db->tableColumnExists('il_meta_contribute', 'descr_lang')) {
            $this->db->addTableColumn(
                'il_meta_contribute',
                'descr_lang',
                [
                    'type' => ilDBConstants::T_TEXT,
                    'length' => 2
                ]
            );
        }
    }

    /**
     * Add two columns to the il_meta_annotation table to store the
     * descrption of the date and its language.
     */
    public function step_3(): void
    {
        if (!$this->db->tableColumnExists('il_meta_annotation', 'a_date_descr')) {
            $this->db->addTableColumn(
                'il_meta_annotation',
                'a_date_descr',
                [
                    'type' => ilDBConstants::T_CLOB,
                ]
            );
        }
        if (!$this->db->tableColumnExists('il_meta_annotation', 'date_descr_lang')) {
            $this->db->addTableColumn(
                'il_meta_annotation',
                'date_descr_lang',
                [
                    'type' => ilDBConstants::T_TEXT,
                    'length' => 2
                ]
            );
        }
    }

    /**
     * Move the columns 'coverage' and 'coverage_language' from il_meta_general
     * to their own table, to allow multiple coverages per MD set.
     * ------------------------------------------------
     * First, create the new table 'il_meta_coverage'.
     */
    public function step_4(): void
    {
        if (!$this->db->tableExists('il_meta_coverage')) {
            $this->db->createTable(
                'il_meta_coverage',
                [
                    'meta_coverage_id' => [
                        'type' => ilDBConstants::T_INTEGER,
                        'notnull' => true,
                        'default' => 0
                    ],
                    'rbac_id' => [
                        'type' => ilDBConstants::T_INTEGER,
                    ],
                    'obj_id' => [
                        'type' => ilDBConstants::T_INTEGER,
                    ],
                    'obj_type' => [
                        'type' => ilDBConstants::T_TEXT,
                        'length' => 6
                    ],
                    'parent_type' => [
                        'type' => ilDBConstants::T_TEXT,
                        'length' => 16
                    ],
                    'parent_id' => [
                        'type' => ilDBConstants::T_INTEGER,
                    ],
                    'coverage' => [
                        'type' => ilDBConstants::T_TEXT,
                        'length' => 4000
                    ],
                    'coverage_language' => [
                        'type' => ilDBConstants::T_TEXT,
                        'length' => 2
                    ]
                ]
            );
        }
    }
    /**
     * Move the columns 'coverage' and 'coverage_language' from il_meta_general
     * to their own table, to allow multiple coverages per MD set.
     * ------------------------------------------------
     * Second, move the data to the new table.
     */
    public function step_5(): void
    {
        $this->db->manipulate(
            "INSERT INTO il_meta_coverage " .
            "(meta_coverage_id, rbac_id, obj_id, obj_type, parent_type, " .
            "parent_id, coverage, coverage_language) SELECT " .
            "meta_general_id, rbac_id, obj_id, obj_type, 'meta_general', " .
            "meta_general_id, coverage, coverage_language " .
            "FROM il_meta_general WHERE CHAR_LENGTH(coverage) > 0 " .
            "OR CHAR_LENGTH(coverage_language) > 0"
        );
    }

    /**
     * Move the columns 'coverage' and 'coverage_language' from il_meta_general
     * to their own table, to allow multiple coverages per MD set.
     * ------------------------------------------------
     * Third, create a sequence for the new table.
     */
    public function step_6(): void
    {
        $res = $this->db->query(
            "SELECT MAX(meta_coverage_id) AS max FROM il_meta_coverage"
        );
        while ($row = $this->db->fetchAssoc($res)) {
            $max = $row['max'];
        }
        $this->db->createSequence(
            'il_meta_coverage',
            (int) ($max ?? 0) + 1
        );
    }

    /**
     * Move the columns 'coverage' and 'coverage_language' from il_meta_general
     * to their own table, to allow multiple coverages per MD set.
     * ------------------------------------------------
     * Fourth, cleanup: add index to new table, remove columns from
     * il_meta_general.
     */
    public function step_7(): void
    {
        $this->db->addIndex('il_meta_coverage', ['rbac_id', 'obj_id'], 'i1');

        $this->db->dropTableColumn('il_meta_general', 'coverage');
        $this->db->dropTableColumn('il_meta_general', 'coverage_language');
    }
}
