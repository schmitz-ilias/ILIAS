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
    /**
     * Entries in the expected params with this value should
     * be ignored when reading.
     */
    public const EXP_MD_ID = 'md_id';
    public const EXP_PARENT_MD_ID = 'parent_md_id';
    public const EXP_SUPER_MD_ID = 'super_md_id';

    /**
     * Entries in the expected params with this value should
     * be ignored when reading or deleting.
     */
    public const EXP_DATA = 'md_data';

    public const RES_MD_ID = 'md_id';
    public const RES_DATA = 'md_data';

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

    protected ilMDTagFactory $factory;
    protected ilDBInterface $db;

    public function __construct(
        ilMDTagFactory $factory,
        ilDBInterface $db
    ) {
        $this->factory = $factory;
        $this->db = $db;
    }

    /**
     * Returns a LOM structure in read mode, with a database
     * tag on every element.
     */
    public function getStructureWithTags(): ilMDLOMDatabaseStructure
    {
        $structure = new ilMDLOMDatabaseStructure();
        $structure
            ->movePointerToRoot()
            ->setTagAtPointer(
                $this->factory->databaseTag(
                    '',
                    "SELECT 0 as " . self::RES_MD_ID,
                    '',
                    '',
                    '',
                    []
                )
            );
        $this->setTagsForGeneral($structure);
        $this->setTagsForLifeCycle($structure);
        $this->setTagsForMetaMetadata($structure);
        return $structure->switchToReadMode()
                         ->movePointerToRoot();
    }

    protected function setTagsForGeneral(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('general')
            ->setTagAtPointer(
                $this->getTagForTableContainer('general')
                     ->withIsParent(true)
            );
        $this
            ->setTagsForIdentifier(
                $structure,
                'identifier',
                'meta_general'
            )
            ->movePointerToSubElement('title')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'general',
                    ['title', 'title_language']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'general',
                'title',
                'title_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setTagAtPointer(
                $this->getTagForTableDataWithParent(
                    'language',
                    'language',
                    'meta_general'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->getTagForTableContainerWithParent(
                    'description',
                    'meta_general'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'description',
                'description',
                'description_language',
                'meta_general'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('keyword')
            ->setTagAtPointer(
                $this->getTagForTableContainerWithParent(
                    'keyword',
                    'meta_general'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'keyword',
                'keyword',
                'keyword_language',
                'meta_general'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('coverage')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'general',
                    ['coverage', 'coverage_language']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'general',
                'coverage',
                'coverage_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('structure')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'general',
                    ['general_structure']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'general',
                'general_structure'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('aggregationLevel')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'general',
                    ['general_aggl']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'general',
                'general_aggl'
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForLifeCycle(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('lifeCycle')
            ->setTagAtPointer(
                $this->getTagForTableContainer('lifecycle')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('version')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'lifecycle',
                    ['meta_version', 'version_language']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'lifecycle',
                'meta_version',
                'version_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('status')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'lifecycle',
                    ['lifecycle_status']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'lifecycle',
                'lifecycle_status'
            )
            ->movePointerToSuperElement();
        $this->setTagsForContribute(
            $structure,
            'meta_lifecycle'
        );

        return $structure->movePointerToRoot();
    }

    protected function setTagsForMetaMetadata(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('metaMetadata')
            ->setTagAtPointer(
                $this->getTagForTableContainer('meta_data')
                     ->withIsParent(true)
            );
        $this
            ->setTagsForIdentifier(
                $structure,
                'identifier',
                'meta_meta_data'
            );
        $this
            ->setTagsForContribute(
                $structure,
                'meta_meta_data'
            )
            ->movePointerToSubElement('metadataSchema')
            ->setTagAtPointer(
                $this->getTagForData(
                    'meta_data',
                    'meta_data_scheme'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setTagAtPointer(
                $this->getTagForData(
                    'meta_data',
                    'language'
                )
            );

        return $structure->movePointerToRoot();
    }

    protected function setTagsForTechnical(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('technical')
            ->setTagAtPointer(
                $this->getTagForTableContainer('technical')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('format')
            ->setTagAtPointer(
                $this->getTagForTableData(
                    'format',
                    'format'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('size')
            ->setTagAtPointer(
                $this->getTagForData(
                    'technical',
                    't_size'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('location')
            ->setTagAtPointer(
                $this->getTagForTableDataWithParent(
                    'location',
                    'location',
                    'meta_technical'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('requirement')
            ->setTagAtPointer(
                $this->getTagForTableContainerWithParent(
                    'requirement',
                    'meta_technical'
                )
            );
        //TODO continue with orComposite
        return $structure->movePointerToRoot();
    }

    //common elements
    protected function setTagsForIdentifier(
        ilMDLOMDatabaseStructure $structure,
        string $table,
        string $parent_type
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToSubElement('identifier')
            ->setTagAtPointer(
                $this->getTagForTableContainerWithParent(
                    $table,
                    $parent_type
                )
            )
            ->movePointerToSubElement('catalog')
            ->setTagAtPointer(
                $this->getTagForDataWithParent(
                    $table,
                    'catalog',
                    $parent_type
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this->getTagForDataWithParent(
                    $table,
                    'entry',
                    $parent_type
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement();

        return $structure;
    }

    protected function setTagsForContribute(
        ilMDLOMDatabaseStructure $structure,
        string $parent_type
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToSubElement('contribute')
            ->setTagAtPointer(
                $this->getTagForTableContainerWithParent(
                    'contribute',
                    $parent_type
                )->withIsParent(true)
            )
            ->movePointerToSubElement('role')
            ->setTagAtPointer(
                $this->getTagForNonTableContainerWithParent(
                    'contribute',
                    ['role'],
                    $parent_type
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'contribute',
                'role',
                $parent_type
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entity')
            ->setTagAtPointer(
                $this->getTagForTableDataWithParent(
                    'entity',
                    'entity',
                    'meta_contribute'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('date')
            ->setTagAtPointer(
                $this->getTagForNonTableContainerWithParent(
                    'contribute',
                    ['c_date', 'c_date_descr', 'descr_lang'],
                    $parent_type
                )
            )
            ->movePointerToSubElement('dateTime')
            ->setTagAtPointer(
                $this->getTagForDataWithParent(
                    'contribute',
                    'c_date',
                    $parent_type
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->getTagForNonTableContainerWithParent(
                    'contribute',
                    ['c_date_descr', 'descr_lang'],
                    $parent_type
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'contribute',
                'c_date_descr',
                'descr_lang',
                $parent_type
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSuperElement();

        return $structure;
    }

    protected function setTagsForLangStringSubElements(
        ilMDLOMDatabaseStructure $structure,
        string $table,
        string $field_string,
        string $field_lang,
        string $parent_type = ''
    ): ilMDLOMDatabaseStructure {
        if ($parent_type) {
            $tag_string = $this->getTagForDataWithParent(
                $table,
                $field_string,
                $parent_type
            );
            $tag_lang = $this->getTagForDataWithParent(
                $table,
                $field_lang,
                $parent_type
            );
        } else {
            $tag_string = $this->getTagForData(
                $table,
                $field_string
            );
            $tag_lang = $this->getTagForData(
                $table,
                $field_lang
            );
        }
        $structure
            ->movePointerToSubElement('string')
            ->setTagAtPointer($tag_string)
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setTagAtPointer($tag_lang)
            ->movePointerToSuperElement();

        return $structure;
    }

    protected function setTagsForVocabSubElements(
        ilMDLOMDatabaseStructure $structure,
        string $table,
        string $field_value,
        string $parent_type = ''
    ): ilMDLOMDatabaseStructure {
        if ($parent_type) {
            $tag_value = $this->getTagForDataWithParent(
                $table,
                $field_value,
                $parent_type
            );
        } else {
            $tag_value = $this->getTagForData(
                $table,
                $field_value
            );
        }
        $structure
            ->movePointerToSubElement('value')
            ->setTagAtPointer($tag_value)
            ->movePointerToSuperElement()
            ->movePointerToSubElement('source')
            ->setTagAtPointer(
                $this->factory->databaseTag(
                    '',
                    "SELECT '" . ilMDLOMVocabulariesDictionary::SOURCE .
                    "' AS " . self::RES_DATA . ', 0 AS ' . self::RES_MD_ID,
                    '',
                    '',
                    '',
                    []
                )
            )
           ->movePointerToSuperElement();

        return $structure;
    }

    /**
     * Returns the appropriate database tag for a container element
     * with its own table.
     */
    protected function getTagForTableContainer(
        string $table,
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', rbac_id, obj_id, obj_type) VALUES (%s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $delete =
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create,
            $read,
            '',
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * with its own table, but which has a parent element.
     */
    protected function getTagForTableContainerWithParent(
        string $table,
        string $parent_type
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (%s, ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            '%s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $delete =
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create,
            $read,
            '',
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * without its own table.
     * @param string    $table
     * @param string[]  $fields
     * @return ilMDDatabaseTag
     */
    protected function getTagForNonTableContainer(
        string $table,
        array $fields
    ): ilMDDatabaseTag {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDDatabaseException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db->quoteIdentifier($field) .
                ') > 0 AND ';
        }
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $read_fields .
            ' rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $delete_fields = '';
        foreach ($fields as $field) {
            $delete_fields .= $this->db->quoteIdentifier($field) . " = '', ";
        }
        $delete_fields = substr($delete_fields, 0, -2) . ' ';
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $delete_fields .
            'WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            '',
            $read,
            '',
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a container element
     * without its own table, but with a parent.
     * @param string    $table
     * @param string[]  $fields
     * @param string    $parent_type
     * @return ilMDDatabaseTag
     */
    protected function getTagForNonTableContainerWithParent(
        string $table,
        array $fields,
        string $parent_type
    ): ilMDDatabaseTag {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDDatabaseException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db->quoteIdentifier($field) .
                ') > 0 AND ';
        }
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $read_fields . ' parent_type = ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $delete_fields = '';
        foreach ($fields as $field) {
            $delete_fields .= $this->db->quoteIdentifier($field) . " = '', ";
        }
        $delete_fields = substr($delete_fields, 0, -2) . ' ';
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $delete_fields .
            'WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            '',
            $read,
            '',
            $delete,
            self::TABLES[$table],
            [self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * without its own table, but where a parent has to be given.
     */
    protected function getTagForDataWithParent(
        string $table,
        string $field,
        string $parent_type
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create_and_update =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $read =
            'SELECT ' . $this->db->quoteIdentifier($field) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_DATA) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' = %s AND parent_type = ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . " = ''" .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_SUPER_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * with its own table.
     */
    protected function getTagForTableData(
        string $table,
        string $field
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier($field) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', rbac_id, obj_id, obj_type) VALUES (%s, %s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db->quoteIdentifier($field) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_DATA) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $update =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $delete =
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create,
            $read,
            $update,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * with its own table, and which has a parent element.
     */
    protected function getTagForTableDataWithParent(
        string $table,
        string $field,
        string $parent_type
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier($field) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (%s, %s ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) . ', ' .
            '%s, %s, %s, %s)';
        $read =
            'SELECT ' . $this->db->quoteIdentifier($field) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_DATA) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE parent_type = ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $update =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $delete =
            'DELETE FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND parent_type = ' . $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create,
            $read,
            $update,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_MD_ID, self::EXP_PARENT_MD_ID]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * without its own table.
     */
    protected function getTagForData(
        string $table,
        string $field
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create_and_update =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . ' = %s' .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $read =
            'SELECT ' . $this->db->quoteIdentifier($field) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_DATA) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME[$table]);
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' SET ' . $this->db->quoteIdentifier($field) . " = ''" .
            ' WHERE ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
            ' AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            self::TABLES[$table],
            [self::EXP_DATA, self::EXP_SUPER_MD_ID]
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
