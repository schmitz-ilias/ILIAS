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
        $this->setMarkersForGeneral($structure);
        return $structure->switchToReadMode();
    }

    protected function setMarkersForGeneral(
        ilMDLOMStructure $structure
    ): ilMDLOMStructure {
        $structure->movePointerToRoot()
                  ->movePointerToSubElement('general')
                  ->setMarkerAtPointer(
                      $this->getMarkerForTableContainer('general')
                  )->movePointerToSubElement('identifier');
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
            ', rbac_id, obj_id, obj_type) VALUES (' .
            $this->db->nextId(self::TABLES[$table]) . ', ?, ?, ?);',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . $this->db->quoteIdentifier(self::TABLES['general']) .
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
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES['general']) .
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
            [self::EXP_MD_ID]
        );
    }

    /**
     * Returns the appropriate database marker for a container element
     * with its own table.
     */
    protected function getMarkerForTableContainerWithParent(
        string $table,
        string $parent_type,
        string $parent_table
    ): ilMDDatabaseMarker {
        $this->checkTable($table);
        $this->checkTable($parent_table);

        //TODO Do this by joining the tables instead, so that the expected params don't have to be doubled
        $parent_id_query = '(SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$parent_table]) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$parent_table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$parent_table]) . ' = ?' .
            ' AND rbac_id = ? AND obj_id = ? AND obj_type = ?)';

        $create = $this->db->prepareManip(
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ',parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (' .
            $this->db->nextId(self::TABLES[$table]) . ', ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            $parent_id_query . ' ?, ?, ?);',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT
            ]
        );
        $read = $this->db->prepare(
            'SELECT COUNT(*) FROM ' . $this->db->quoteIdentifier(self::TABLES['general']) .
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
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES['general']) .
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
            [self::EXP_MD_ID]
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
