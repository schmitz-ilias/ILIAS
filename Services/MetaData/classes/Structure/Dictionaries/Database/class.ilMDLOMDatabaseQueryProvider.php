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

use ilMDLOMDatabaseDictionary as dbd;

/**
 * @author Tim Schmitz <schmitz@leifos.de>
 */
class ilMDLOMDatabaseQueryProvider
{
    protected ilMDTagFactory $factory;
    protected ?ilDBInterface $db;

    /**
     * The DB interface is only allowed to be null here because the
     * editor needs to know which elements can be created (meaning
     * have a non-null create query), so the editor needs this
     * dictionary, but I don't want to pass the DB interface there.
     * This should be changed when we change the DB structure to
     * something that can work better with the new editor.
     */
    public function __construct(
        ilMDTagFactory $factory,
        ?ilDBInterface $db
    ) {
        $this->factory = $factory;
        $this->db = $db;
    }

    /**
     * Returns the appropriate database tag for a container element
     * with its own table.
     */
    public function getTagForTableContainer(
        string $table,
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' (' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ', rbac_id, obj_id, obj_type) VALUES (%s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $delete =
            'DELETE FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create,
            $read,
            '',
            $delete,
            dbd::TABLES[$table],
            [dbd::EXP_MD_ID, dbd::EXP_OBJ_IDS]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * with its own table, but which has a parent element.
     */
    public function getTagForTableContainerWithParent(
        string $table,
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' (' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (%s, ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            '%s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $delete =
            'DELETE FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create,
            $read,
            '',
            $delete,
            dbd::TABLES[$table],
            [
                dbd::EXP_MD_ID,
                $second_parent ?
                    dbd::EXP_SECOND_PARENT_MD_ID :
                    dbd::EXP_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * without its own table.
     * @param string    $table
     * @param string[]  $fields
     * @return ilMDDatabaseTag
     */
    public function getTagForNonTableContainer(
        string $table,
        array $fields
    ): ilMDDatabaseTag {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDRepositoryException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '(';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db?->quoteIdentifier($field) .
                ') > 0 OR ';
        }
        $read_fields = substr($read_fields, 0, -3) . ') AND ';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $read_fields .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s AND' .
            ' rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $delete_fields = '';
        foreach ($fields as $field) {
            $delete_fields .= $this->db?->quoteIdentifier($field) . " = '', ";
        }
        $delete_fields = substr($delete_fields, 0, -2) . ' ';
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $delete_fields .
            'WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            '',
            $read,
            '',
            $delete,
            dbd::TABLES[$table],
            [dbd::EXP_MD_ID, dbd::EXP_SUPER_MD_ID, dbd::EXP_OBJ_IDS]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * without its own table, but with a parent.
     * @param string   $table
     * @param string[] $fields
     * @param string   $parent_type
     * @param bool     $second_parent
     * @return ilMDDatabaseTag
     */
    public function getTagForNonTableContainerWithParent(
        string $table,
        array $fields,
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDRepositoryException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '(';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db?->quoteIdentifier($field) .
                ') > 0 OR ';
        }
        $read_fields = substr($read_fields, 0, -3) . ') AND ';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $read_fields . ' parent_type = ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) . ' AND ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $delete_fields = '';
        foreach ($fields as $field) {
            $delete_fields .= $this->db?->quoteIdentifier($field) . " = '', ";
        }
        $delete_fields = substr($delete_fields, 0, -2) . ' ';
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $delete_fields .
            'WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            '',
            $read,
            '',
            $delete,
            dbd::TABLES[$table],
            [
                dbd::EXP_MD_ID,
                dbd::EXP_SUPER_MD_ID,
                $second_parent ?
                    dbd::EXP_SECOND_PARENT_MD_ID :
                    dbd::EXP_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for the technical: orComposite
     * container element, which is a special case.
     */
    public function getTagForOrComposite(): ilMDDatabaseTag
    {
        $read =
            'SELECT ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            " FROM ((SELECT '" . dbd::MD_ID_OS . "'" .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type, ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' WHERE (CHAR_LENGTH(operating_system_name) > 0 OR' .
            ' CHAR_LENGTH(os_min_version) > 0 OR CHAR_LENGTH(os_max_version) > 0)' .
            ') UNION (' .
            "SELECT '" . dbd::MD_ID_BROWSER . "'" .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type, ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' WHERE (CHAR_LENGTH(browser_name) > 0 OR' .
            ' CHAR_LENGTH(browser_minimum_version) > 0 OR CHAR_LENGTH(browser_maximum_version) > 0)))' .
            " AS u WHERE u.parent_type = 'meta_technical' AND u." .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND u.parent_id = %s AND u.rbac_id = %s AND u.obj_id = %s AND u.obj_type = %s' .
            ' ORDER BY u.' . $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']);
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' SET operating_system_name = CASE %s WHEN ' . dbd::MD_ID_OS . " THEN ''" .
            ' ELSE operating_system_name END, ' .
            ' os_min_version = CASE %s WHEN ' . dbd::MD_ID_OS . " THEN ''" .
            ' ELSE os_min_version END, ' .
            ' os_max_version = CASE %s WHEN ' . dbd::MD_ID_OS . " THEN ''" .
            ' ELSE os_max_version END, ' .
            ' browser_name = CASE %s WHEN ' . dbd::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_name END, ' .
            ' browser_minimum_version = CASE %s WHEN ' . dbd::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_minimum_version END, ' .
            ' browser_maximum_version = CASE %s WHEN ' . dbd::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_maximum_version END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            '',
            $read,
            '',
            $delete,
            dbd::TABLES['requirement'],
            [
                dbd::EXP_MD_ID,
                dbd::EXP_MD_ID,
                dbd::EXP_MD_ID,
                dbd::EXP_MD_ID,
                dbd::EXP_MD_ID,
                dbd::EXP_MD_ID,
                dbd::EXP_SUPER_MD_ID,
                dbd::EXP_SECOND_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for the technical: orComposite:
     * name container element, which is a special case.
     */
    public function getTagForOrCompositeName(): ilMDDatabaseTag
    {
        $read =
            "SELECT '%s' AS " . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' WHERE CASE %s WHEN ' . dbd::MD_ID_OS . ' THEN CHAR_LENGTH(operating_system_name)' .
            ' WHEN ' . dbd::MD_ID_BROWSER . ' THEN CHAR_LENGTH(browser_name) END > 0 ' .
            " AND parent_type = 'meta_technical' AND " .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']);
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' SET operating_system_name = CASE %s WHEN ' . dbd::MD_ID_OS . " THEN ''" .
            ' ELSE operating_system_name END, ' .
            ' browser_name = CASE %s WHEN ' . dbd::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_name END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            '',
            $read,
            '',
            $delete,
            dbd::TABLES['requirement'],
            [
                dbd::EXP_SUPER_MD_ID,
                dbd::EXP_SUPER_MD_ID,
                dbd::EXP_PARENT_MD_ID,
                dbd::EXP_SECOND_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for data-carrying sub-elements
     * of technical: orComposite element, which are special cases.
     */
    public function getTagForOrCompositeData(
        string $field_os,
        string $field_browser
    ): ilMDDatabaseTag {
        $read =
            "SELECT '%s' AS " . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ', CASE %s WHEN ' . dbd::MD_ID_OS . ' THEN ' . $this->db?->quoteIdentifier($field_os) .
            ' WHEN ' . dbd::MD_ID_BROWSER . ' THEN  ' . $this->db?->quoteIdentifier($field_browser) .
            ' END AS ' . $this->db?->quoteIdentifier(dbd::RES_DATA) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']);
        $create_and_update =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' SET ' . $this->db?->quoteIdentifier($field_os) . ' = CASE %s WHEN ' .
            dbd::MD_ID_OS . ' THEN %s' .
            ' ELSE ' . $this->db?->quoteIdentifier($field_os) . ' END, ' .
            $this->db?->quoteIdentifier($field_browser) . ' = CASE %s WHEN ' .
            dbd::MD_ID_BROWSER . ' THEN %s' .
            ' ELSE ' . $this->db?->quoteIdentifier($field_browser) . ' END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES['requirement']) .
            ' SET ' . $this->db?->quoteIdentifier($field_os) . ' = CASE %s WHEN ' .
            dbd::MD_ID_OS . " THEN ''" .
            ' ELSE ' . $this->db?->quoteIdentifier($field_os) . ' END, ' .
            $this->db?->quoteIdentifier($field_browser) . ' = CASE %s WHEN ' .
            dbd::MD_ID_BROWSER . " THEN ''" .
            ' ELSE ' . $this->db?->quoteIdentifier($field_browser) . ' END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db?->quoteIdentifier(dbd::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            dbd::TABLES['requirement'],
            [
                dbd::EXP_SUPER_MD_ID,
                dbd::EXP_DATA,
                dbd::EXP_SUPER_MD_ID,
                dbd::EXP_DATA,
                dbd::EXP_PARENT_MD_ID,
                dbd::EXP_SECOND_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * without its own table, but with a parent, where the corresponding
     * fields are scattered across two tables.
     * @param string   $first_table
     * @param string[] $first_fields
     * @param string   $second_table
     * @param string[] $second_fields
     * @param string   $parent_type
     * @param bool     $second_parent
     * @return ilMDDatabaseTag
     */
    public function getTagForNonTableContainerWithParentAcrossTwoTables(
        string $first_table,
        array $first_fields,
        string $second_table,
        array $second_fields,
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($first_table);
        $this->checkTable($second_table);
        if (empty($first_fields) || empty($second_fields)) {
            throw new ilMDRepositoryException(
                'A container element can not be empty.'
            );
        }
        $shared_fields = [
            'parent_type',
            'parent_id',
            'rbac_id',
            'obj_id',
            'obj_type'
        ];
        $join_select = '';
        foreach ($shared_fields as $field) {
            $join_select .= 't1.' . $field . ' AS ' . $field . '_1,' .
                ' t2.' . $field . ' AS ' . $field . '_2, ';
        }
        foreach ($first_fields as $field) {
            $join_select .= 't1.' . $field . ' AS ' . $field . '_1, ';
        }
        foreach ($second_fields as $field) {
            $join_select .= 't2.' . $field . ' AS ' . $field . '_2, ';
        }
        $join_select = substr($join_select, 0, -2);
        $join_condition = '';
        foreach ($shared_fields as $field) {
            $join_condition .= 't1.' . $field . ' = t2.' . $field . ' AND ';
        }
        $join_condition = substr($join_condition, 0, -4);
        $read_fields_1 = '(';
        foreach ($first_fields as $field) {
            $read_fields_1 .= 'CHAR_LENGTH(' .
                $this->db?->quoteIdentifier($field . '_1') .
                ') > 0 OR ';
        }
        $read_fields_1 = substr($read_fields_1, 0, -3) . ') AND ';
        $read_fields_2 = '(';
        foreach ($second_fields as $field) {
            $read_fields_2 .= 'CHAR_LENGTH(' .
                $this->db?->quoteIdentifier($field . '_2') .
                ') > 0 OR ';
        }
        $read_fields_2 = substr($read_fields_2, 0, -3) . ') AND ';
        $read =
            'SELECT %s' .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ((SELECT ' . $join_select .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$first_table]) .
            ' AS t1 LEFT OUTER JOIN ' . $this->db?->quoteIdentifier(dbd::TABLES[$second_table]) .
            ' AS t2 ON ' . $join_condition . ') UNION (' .
            'SELECT ' . $join_select .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$first_table]) .
            ' AS t1 RIGHT OUTER JOIN ' . $this->db?->quoteIdentifier(dbd::TABLES[$second_table]) .
            ' AS t2 ON ' . $join_condition .
            ' )) AS t WHERE (' . $read_fields_1 . ' t.parent_type_1 = ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND t.parent_id_1 = %s' .
            ' AND t.rbac_id_1 = %s AND t.obj_id_1 = %s AND t.obj_type_1 = %s)' .
            ' OR (' . $read_fields_2 . ' t.parent_type_2 = ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND t.parent_id_2 = %s' .
            ' AND t.rbac_id_2 = %s AND t.obj_id_2 = %s AND t.obj_type_2 = %s)' .
            ' ORDER BY t.parent_id_1, t.parent_id_2';

        $parent = $second_parent ?
            dbd::EXP_SECOND_PARENT_MD_ID :
            dbd::EXP_PARENT_MD_ID;
        return $this->factory->database(
            '',
            $read,
            '',
            '',
            '',
            [
                $parent,
                $parent,
                dbd::EXP_OBJ_IDS,
                $parent,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * without its own table, but where a parent has to be given.
     */
    public function getTagForDataWithParent(
        string $table,
        string $field,
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create_and_update =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $this->db?->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier($field) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_DATA) . ', ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' = %s AND parent_type = ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $this->db?->quoteIdentifier($field) . " = ''" .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            dbd::TABLES[$table],
            [
                dbd::EXP_DATA,
                dbd::EXP_SUPER_MD_ID,
                $second_parent ?
                    dbd::EXP_SECOND_PARENT_MD_ID :
                    dbd::EXP_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * with its own table.
     */
    public function getTagForTableData(
        string $table,
        string $field
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' (' . $this->db?->quoteIdentifier($field) . ', ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ', rbac_id, obj_id, obj_type) VALUES (%s, %s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier($field) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_DATA) . ', ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $update =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $this->db?->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $delete =
            'DELETE FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create,
            $read,
            $update,
            $delete,
            dbd::TABLES[$table],
            [
                dbd::EXP_DATA,
                dbd::EXP_MD_ID,
                dbd::EXP_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * with its own table, and which has a parent element.
     */
    public function getTagForTableDataWithParent(
        string $table,
        string $field,
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' (' . $this->db?->quoteIdentifier($field) . ', ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (%s, %s, ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            '%s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier($field) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_DATA) . ', ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE parent_type = ' .
            $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $update =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $this->db?->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $delete =
            'DELETE FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db?->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create,
            $read,
            $update,
            $delete,
            dbd::TABLES[$table],
            [
                dbd::EXP_DATA,
                dbd::EXP_MD_ID,
                $second_parent ?
                    dbd::EXP_SECOND_PARENT_MD_ID :
                    dbd::EXP_PARENT_MD_ID,
                dbd::EXP_OBJ_IDS
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * without its own table.
     */
    public function getTagForData(
        string $table,
        string $field
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create_and_update =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $this->db?->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $read =
            'SELECT ' . $this->db?->quoteIdentifier($field) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_DATA) . ', ' .
            $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' AS ' . $this->db?->quoteIdentifier(dbd::RES_MD_ID) .
            ' FROM ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) .
            ' = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]);
        $delete =
            'UPDATE ' . $this->db?->quoteIdentifier(dbd::TABLES[$table]) .
            ' SET ' . $this->db?->quoteIdentifier($field) . " = ''" .
            ' WHERE ' . $this->db?->quoteIdentifier(dbd::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->database(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            dbd::TABLES[$table],
            [dbd::EXP_DATA, dbd::EXP_SUPER_MD_ID, dbd::EXP_OBJ_IDS]
        );
    }

    /**
     * @throws ilMDRepositoryException
     */
    protected function checkTable(string $table): void
    {
        if (
            !array_key_exists($table, dbd::TABLES) ||
            !array_key_exists($table, dbd::ID_NAME)
        ) {
            throw new ilMDRepositoryException('Invalid MD table.');
        }
    }
}
