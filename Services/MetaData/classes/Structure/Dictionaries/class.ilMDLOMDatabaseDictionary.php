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
class ilMDLOMDatabaseDictionary implements ilMDDictionary
{
    public const EXP_MD_ID = 'md_id';
    public const EXP_PARENT_MD_ID = 'parent_md_id';

    /**
     * Entries in the expected params with this value should
     * be ignored when reading or deleting.
     */
    public const EXP_DATA = 'md_data';

    public const TABLES = [
        'annotation' => 'il_meta_annotation',
        'classification' => 'il_meta_classification',
        'contribute' => 'il_meta_contribute',
        'description' => 'il_meta_description',
        'educational' => 'il_meta_educational',
        'entity' => 'il_meta_entity',
        'format' => 'il_meta_format',
        'general' => 'il_meta_general',
        'identifier' => 'il_meta_identifier',
        'identifier_' => 'il_meta_identifier_',
        'keyword' => 'il_meta_keyword',
        'language' => 'il_meta_language',
        'lifecycle' => 'il_meta_lifecycle',
        'location' => 'il_meta_location',
        'meta_data' => 'il_meta_meta_data',
        'relation' => 'il_meta_relation',
        'requirement' => 'il_meta_requirement',
        'rights' => 'il_meta_rights',
        'tar' => 'il_meta_tar',
        'taxon' => 'il_meta_taxon',
        'taxon_path' => 'il_meta_taxon_path',
        'technical' => 'il_meta_technical'
    ];

    public const ID_NAME = [
        'annotation' => 'meta_annotation_id',
        'classification' => 'meta_classification_id',
        'contribute' => 'meta_contribute_id',
        'description' => 'meta_description_id',
        'educational' => 'meta_educational_id',
        'entity' => 'meta_entity_id',
        'format' => 'meta_format_id',
        'general' => 'meta_general_id',
        'identifier' => 'meta_identifier_id',
        'identifier_' => 'meta_identifier__id',
        'keyword' => 'meta_keyword_id',
        'language' => 'meta_language_id',
        'lifecycle' => 'meta_lifecycle_id',
        'location' => 'meta_location_id',
        'meta_data' => 'meta_meta_data_id',
        'relation' => 'meta_relation_id',
        'requirement' => 'meta_requirement_id',
        'rights' => 'meta_rights_id',
        'tar' => 'meta_tar_id',
        'taxon' => 'meta_taxon_id',
        'taxon_path' => 'meta_taxon_path_id',
        'technical' => 'meta_technical_id'
    ];

    protected ilMDMarkerFactory $factory;
    protected ilDBInterface $db;

    public function __construct(
        ilMDMarkerFactory $factory,
        ilDBInterface $db
    ) {
        $this->factory = $factory;
        $this->db = $db;
    }

    /**
     * Returns a LOM structure in read mode, with a database
     * marker on every element except the root.
     */
    public function getStructureWithMarkers(): ilMDLOMStructure
    {
        $structure = new ilMDLOMStructure();
        $structure = $this->setMarkersForGeneral($structure);
        $structure = $this->setMarkersForLifecycle($structure);
        //TODO continue with meta-metadata (and maybe streamline this mess a bit?)
        return $structure->switchToReadMode()
                         ->movePointerToRoot();
    }

    protected function setMarkersForGeneral(
        ilMDLOMStructure $structure
    ): ilMDLOMStructure {
        $structure = $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('general')
            ->setMarkerAtPointer(
                $this->getMarkerForTableContainer('general')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('identifier')
            ->setMarkerAtPointer(
                $this->getMarkerForTableContainerWithParent(
                    'identifier',
                    'meta_general'
                )
            )
            ->movePointerToSubElement('catalog')
            ->setMarkerAtPointer(
                $this->getMarkerForDataWithParent(
                    'identifier',
                    'catalog',
                    'meta_general'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setMarkerAtPointer(
                $this->getMarkerForDataWithParent(
                    'identifier',
                    'entry',
                    'meta_general'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('title')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainer(
                    'general',
                    ['title', 'title_language']
                )
            );
        $structure = $this
            ->setMarkersForLangString(
                $structure,
                'general',
                'title',
                'title_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setMarkerAtPointer(
                $this->getMarkerForTableDataWithParent(
                    'language',
                    'language',
                    'meta_general'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setMarkerAtPointer(
                $this->getMarkerForTableContainerWithParent(
                    'description',
                    'meta_general'
                )
            );
        $structure = $this
            ->setMarkersForLangString(
                $structure,
                'description',
                'description',
                'description_language',
                'meta_general'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('keyword')
            ->setMarkerAtPointer(
                $this->getMarkerForTableContainerWithParent(
                    'keyword',
                    'meta_general'
                )
            );
        $structure = $this
            ->setMarkersForLangString(
                $structure,
                'keyword',
                'keyword',
                'keyword_language',
                'meta_general'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('coverage')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainer(
                    'general',
                    ['coverage', 'coverage_language']
                )
            );
        $structure = $this
            ->setMarkersForLangString(
                $structure,
                'general',
                'coverage',
                'coverage_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('structure')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainer(
                    'general',
                    ['general_structure']
                )
            );
        $structure = $this
            ->setMarkersForVocab(
                $structure,
                'general',
                'general_structure'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('aggregationLevel')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainer(
                    'general',
                    ['general_aggl']
                )
            );
        $structure = $this
            ->setMarkersForVocab(
                $structure,
                'general',
                'general_aggl'
            );
        return $structure->movePointerToRoot();
    }

    protected function setMarkersForLifeCycle(
        ilMDLOMStructure $structure
    ): ilMDLOMStructure {
        $structure = $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('lifeCycle')
            ->setMarkerAtPointer(
                $this->getMarkerForTableContainer('lifecycle')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('version')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainer(
                    'lifecycle',
                    ['meta_version', 'version_language']
                )
            );
        $structure = $this
            ->setMarkersForLangString(
                $structure,
                'lifecycle',
                'meta_version',
                'version_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('status')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainer(
                    'lifecycle',
                    ['lifecycle_status']
                )
            );
        $structure = $this
            ->setMarkersForVocab(
                $structure,
                'lifecycle',
                'lifecycle_status'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('contribute')
            ->setMarkerAtPointer(
                $this->getMarkerForTableContainerWithParent(
                    'contribute',
                    'meta_lifecycle'
                )->withIsParent(true)
            )
            ->movePointerToSubElement('role')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainerWithParent(
                    'contribute',
                    ['role'],
                    'meta_lifecycle'
                )
            );
        $structure = $this
            ->setMarkersForVocab(
                $structure,
                'contribute',
                'role',
                'meta_lifecycle'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entity')
            ->setMarkerAtPointer(
                $this->getMarkerForTableDataWithParent(
                    'entity',
                    'entity',
                    'meta_contribute'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('date')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainerWithParent(
                    'contribute',
                    ['c_date', 'c_date_descr', 'descr_lang'],
                    'meta_lifecycle'
                )
            )
            ->movePointerToSubElement('dateTime')
            ->setMarkerAtPointer(
                $this->getMarkerForDataWithParent(
                    'contribute',
                    'c_date',
                    'meta_lifecycle'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setMarkerAtPointer(
                $this->getMarkerForNonTableContainerWithParent(
                    'contribute',
                    ['c_date_descr', 'descr_lang'],
                    'meta_lifecycle'
                )
            );
        $structure = $this
            ->setMarkersForLangString(
                $structure,
                'contribute',
                'c_date_descr',
                'descr_lang',
                'meta_lifecycle'
            );
        return $structure->movePointerToRoot();
    }

    protected function setMarkersForLangString(
        ilMDLOMStructure $structure,
        string $table,
        string $field_string,
        string $field_lang,
        string $parent_type = ''
    ): ilMDLOMStructure {
        if ($parent_type) {
            $marker_string = $this->getMarkerForDataWithParent(
                $table,
                $field_string,
                $parent_type
            );
            $marker_lang = $this->getMarkerForDataWithParent(
                $table,
                $field_lang,
                $parent_type
            );
        } else {
            $marker_string = $this->getMarkerForData(
                $table,
                $field_string
            );
            $marker_lang = $this->getMarkerForData(
                $table,
                $field_lang
            );
        }
        $structure = $structure
            ->movePointerToSubElement('string')
            ->setMarkerAtPointer($marker_string)
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setMarkerAtPointer($marker_lang)
            ->movePointerToSuperElement();

        return $structure;
    }

    protected function setMarkersForVocab(
        ilMDLOMStructure $structure,
        string $table,
        string $field_value,
        string $parent_type = ''
    ): ilMDLOMStructure {
        if ($parent_type) {
            $marker_value = $this->getMarkerForDataWithParent(
                $table,
                $field_value,
                $parent_type
            );
        } else {
            $marker_value = $this->getMarkerForData(
                $table,
                $field_value
            );
        }
        $structure = $structure
            ->movePointerToSubElement('value')
            ->setMarkerAtPointer($marker_value)
            ->movePointerToSuperElement()
            ->movePointerToSubElement('source')
            ->setMarkerAtPointer(
                $this->factory->getDatabaseMarker(
                    null,
                    $this->db->prepare("SELECT 'LOMv1.0' as data;"),
                    null,
                    null,
                    ''
                )
            )
           ->movePointerToSuperElement();

        return $structure;
    }

    /**
     * Returns the appropriate database marker for a container element
     * with its own table.
     */
    protected function getMarkerForTableContainer(
        string $table,
    ): ilMDDatabaseMarker {
        $this->checkTable($table);

        $create = $this->db->prepareManip(
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', rbac_id, obj_id, obj_type) VALUES (?, ?, ?, ?);',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete = $this->db->prepareManip(
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            $create,
            $read,
            null,
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a container element
     * with its own table, but which has a parent element.
     */
    protected function getMarkerForTableContainerWithParent(
        string $table,
        string $parent_type
    ): ilMDDatabaseMarker {
        $this->checkTable($table);

        $create = $this->db->prepareManip(
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (?, ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            '?, ?, ?, ?);',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete = $this->db->prepareManip(
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            $create,
            $read,
            null,
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a container element
     * without its own table.
     * @param string    $table
     * @param string[]  $fields
     * @return ilMDDatabaseMarker
     */
    protected function getMarkerForNonTableContainer(
        string $table,
        array $fields
    ): ilMDDatabaseMarker {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDDatabaseException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db->quoteIdentifier($field) .
                ') > 0 ';
        }
        $read = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $read_fields . 'AND ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete_fields = '';
        foreach ($fields as $field) {
            $delete_fields .= $this->db->quoteIdentifier($field) . " = '', ";
        }
        $delete = $this->db->prepareManip(
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $delete_fields .
            'WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            null,
            $read,
            null,
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a container element
     * without its own table, but with a parent.
     * @param string    $table
     * @param string[]  $fields
     * @param string    $parent_type
     * @return ilMDDatabaseMarker
     */
    protected function getMarkerForNonTableContainerWithParent(
        string $table,
        array $fields,
        string $parent_type
    ): ilMDDatabaseMarker {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDDatabaseException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db->quoteIdentifier($field) .
                ') > 0 ';
        }
        $read = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $read_fields . 'AND ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete_fields = '';
        foreach ($fields as $field) {
            $delete_fields .= $this->db->quoteIdentifier($field) . " = '', ";
        }
        $delete = $this->db->prepareManip(
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $delete_fields .
            'WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            null,
            $read,
            null,
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a data element
     * without its own table, but where a parent has to be given.
     */
    protected function getMarkerForDataWithParent(
        string $table,
        string $field,
        string $parent_type
    ): ilMDDatabaseMarker {
        $this->checkTable($table);

        $create_and_update = $this->db->prepareManip(
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . ' = ?' .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_TEXT,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT ' . $this->db->quoteIdentifier($field) . 'AS data' .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete = $this->db->prepareManip(
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . " = ''" .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a data element
     * with its own table, and which has a parent element.
     */
    protected function getMarkerForTableDataWithParent(
        string $table,
        string $field,
        string $parent_type
    ): ilMDDatabaseMarker {
        $this->checkTable($table);

        $create = $this->db->prepareManip(
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier($field) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (?, ? ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            '?, ?, ?, ?);',
            [
                ilDBConstants::T_TEXT,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT ' . $this->db->quoteIdentifier($field) . 'AS data' .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete = $this->db->prepareManip(
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = ? AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            $create,
            $read,
            null,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a data element
     * without its own table.
     */
    protected function getMarkerForData(
        string $table,
        string $field
    ): ilMDDatabaseMarker {
        $this->checkTable($table);

        $create_and_update = $this->db->prepareManip(
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . ' = ?' .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_TEXT,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT ' . $this->db->quoteIdentifier($field) . 'AS data' .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $delete = $this->db->prepareManip(
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . " = ''" .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?;',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );

        return $this->factory->getDatabaseMarker(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_MD_ID]
        );
    }

    /**
     * @throws ilMDDatabaseException
     */
    protected function checkTable(string $table): void
    {
        if (
            !array_key_exists($table, self::TABLES) ||
            !array_key_exists($table, self::ID_NAME)
        ) {
            throw new ilMDDatabaseException('Invalid MD table.');
        }
    }
}
