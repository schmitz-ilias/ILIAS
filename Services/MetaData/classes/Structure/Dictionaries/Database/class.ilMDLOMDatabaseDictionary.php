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
    public const EXP_SECOND_PARENT_MD_ID ='second_parent_md_id';
    public const EXP_SUPER_MD_ID = 'super_md_id';

    /**
     * This flag calls for the triple of rbac_id, obj_id
     * and obj_type that identifies an object with metadata.
     */
    public const EXP_OBJ_IDS = 'obj_ids';

    /**
     * Entries in the expected params with this value should
     * be ignored when reading or deleting.
     */
    public const EXP_DATA = 'md_data';

    public const RES_MD_ID = 'md_id';
    public const RES_DATA = 'md_data';

    /**
     * These are needed to accomodate the special method
     * of saving requirement types in the db.
     */
    public const MD_ID_BROWSER = 101;
    public const MD_ID_OS = 102;

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
        'technical' => 'il_meta_technical',
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
    protected ?ilDBInterface $db;
    protected ilMDLOMDatabaseQueryProvider $query;

    protected ilMDLOMDatabaseStructure $structure;

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
        ?ilDBInterface $db,
        ilMDLOMDatabaseQueryProvider $query
    ) {
        $this->factory = $factory;
        $this->db = $db;
        $this->query = $query;
        $this->structure = $this->initStructureWithTags();
    }

    /**
     * Returns a LOM structure in read mode, with a database
     * tag on every element.
     */
    public function getStructure(): ilMDLOMDatabaseStructure
    {
        return clone $this->structure;
    }

    protected function initStructureWithTags(): ilMDLOMDatabaseStructure
    {
        $structure = new ilMDLOMDatabaseStructure();
        $structure
            ->movePointerToRoot()
            ->setTagAtPointer(
                $this->factory->database(
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
        $this->setTagsForTechnical($structure);
        $this->setTagsForEducational($structure);
        $this->setTagsForRights($structure);
        $this->setTagsForRelation($structure);
        $this->setTagsForAnnotation($structure);
        $this->setTagsForClassification($structure);
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
                $this->query->getTagForTableContainer('general')
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
                $this->query->getTagForNonTableContainer(
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
                $this->query->getTagForTableDataWithParent(
                    'language',
                    'language',
                    'meta_general'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
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
                $this->query->getTagForTableContainerWithParent(
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
                $this->query->getTagForNonTableContainer(
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
                $this->query->getTagForNonTableContainer(
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
                $this->query->getTagForNonTableContainer(
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
                $this->query->getTagForTableContainer('lifecycle')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('version')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
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
                $this->query->getTagForNonTableContainer(
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
                $this->query->getTagForTableContainer('meta_data')
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
                $this->query->getTagForData(
                    'meta_data',
                    'meta_data_scheme'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setTagAtPointer(
                $this->query->getTagForData(
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
                $this->query->getTagForTableContainer('technical')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('format')
            ->setTagAtPointer(
                $this->query->getTagForTableData(
                    'format',
                    'format'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('size')
            ->setTagAtPointer(
                $this->query->getTagForData(
                    'technical',
                    't_size'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('location')
            ->setTagAtPointer(
                $this->query->getTagForTableDataWithParent(
                    'location',
                    'location',
                    'meta_technical'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('requirement')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'requirement',
                    'meta_technical'
                )->withIsParent(true)
            )
            ->movePointerToSubElement('orComposite')
            ->setTagAtPointer(
                $this->query->getTagForOrComposite()
            )
            ->movePointerToSubElement('type')
            ->setTagAtPointer(
                $this->factory->database(
                    '',
                    'SELECT %s AS ' . self::RES_MD_ID,
                    '',
                    '',
                    self::TABLES['requirement'],
                    [self::EXP_SUPER_MD_ID]
                )
            )
            ->movePointerToSubElement('value')
            ->setTagAtPointer(
                $this->factory->database(
                    '',
                    "SELECT '%s' AS " . self::RES_MD_ID .
                    ', CASE %s WHEN ' . self::MD_ID_OS . ' THEN ' .
                    "'operating system'" .
                    ' WHEN ' . self::MD_ID_BROWSER . ' THEN ' .
                    "'browser' END AS " . self::RES_DATA,
                    '',
                    '',
                    self::TABLES['requirement'],
                    [
                        self::EXP_SUPER_MD_ID,
                        self::EXP_SUPER_MD_ID
                    ]
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('source')
            ->setTagAtPointer(
                $this->factory->database(
                    '',
                    "SELECT '" . ilMDLOMVocabulariesDictionary::SOURCE .
                    "' AS " . self::RES_DATA . ', 0 AS ' . self::RES_MD_ID,
                    '',
                    '',
                    '',
                    []
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('name')
            ->setTagAtPointer(
                $this->query->getTagForOrCompositeName()
            )
            ->movePointerToSubElement('value')
            ->setTagAtPointer(
                $this->query->getTagForOrCompositeData(
                    'operating_system_name',
                    'browser_name'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('source')
            ->setTagAtPointer(
                $this->factory->database(
                    '',
                    "SELECT '" . ilMDLOMVocabulariesDictionary::SOURCE .
                    "' AS " . self::RES_DATA . ', 0 AS ' . self::RES_MD_ID,
                    '',
                    '',
                    '',
                    []
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('minimumVersion')
            ->setTagAtPointer(
                $this->query->getTagForOrCompositeData(
                    'os_min_version',
                    'browser_minimum_version'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('maximumVersion')
            ->setTagAtPointer(
                $this->query->getTagForOrCompositeData(
                    'os_max_version',
                    'browser_maximum_version'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('installationRemarks')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'technical',
                    ['ir', 'ir_language']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'technical',
                'ir',
                'ir_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('otherPlatformRequirements')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'technical',
                    ['opr', 'opr_language']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'technical',
                'opr',
                'opr_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('duration')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'technical',
                    ['duration', 'duration_descr', 'duration_descr_lang']
                )
            )
            ->movePointerToSubElement('duration')
            ->setTagAtPointer(
                $this->query->getTagForData(
                    'technical',
                    'duration'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'technical',
                    ['duration_descr', 'duration_descr_lang'],
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'technical',
                'duration_descr',
                'duration_descr_lang',
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForEducational(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('educational')
            ->setTagAtPointer(
                $this->query->getTagForTableContainer('educational')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('interactivityType')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['interactivity_type']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'interactivity_type'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('learningResourceType')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['learning_resource_type']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'learning_resource_type'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('interactivityLevel')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['interactivity_level']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'interactivity_level'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('semanticDensity')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['semantic_density']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'semantic_density'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('intendedEndUserRole')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['intended_end_user_role']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'intended_end_user_role'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('context')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['context']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'context'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('typicalAgeRange')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'tar',
                    'meta_educational'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'tar',
                'typical_age_range',
                'tar_language',
                'meta_educational'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('difficulty')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['difficulty']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'educational',
                'difficulty'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('typicalLearningTime')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['typical_learning_time', 'tlt_descr', 'tlt_descr_lang']
                )
            )
            ->movePointerToSubElement('duration')
            ->setTagAtPointer(
                $this->query->getTagForData(
                    'educational',
                    'typical_learning_time'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'educational',
                    ['tlt_descr', 'tlt_descr_lang'],
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'educational',
                'tlt_descr',
                'tlt_descr_lang',
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'description',
                    'meta_educational'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'description',
                'description',
                'description_language',
                'meta_educational'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('language')
            ->setTagAtPointer(
                $this->query->getTagForTableDataWithParent(
                    'language',
                    'language',
                    'meta_educational'
                )
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRights(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('rights')
            ->setTagAtPointer(
                $this->query->getTagForTableContainer('rights')
            )
            ->movePointerToSubElement('cost')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'rights',
                    ['costs']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'rights',
                'costs'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('copyrightAndOtherRestrictions')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'rights',
                    ['cpr_and_or']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'rights',
                'cpr_and_or'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'rights',
                    ['description', 'description_language']
                )
            );
        $this->setTagsForLangStringSubElements(
            $structure,
            'rights',
            'description',
            'description_language'
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForRelation(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('relation')
            ->setTagAtPointer(
                $this->query->getTagForTableContainer('relation')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('kind')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'relation',
                    ['kind']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'relation',
                'kind'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('resource')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainerWithParentAcrossTwoTables(
                    'identifier_',
                    ['catalog', 'entry'],
                    'description',
                    ['description', 'description_language'],
                    'meta_relation'
                )
            );
        $this
            ->setTagsForIdentifier(
                $structure,
                'identifier_',
                'meta_relation'
            )
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'description',
                    'meta_relation'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'description',
                'description',
                'description_language',
                'meta_relation'
            );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForAnnotation(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('annotation')
            ->setTagAtPointer(
                $this->query->getTagForTableContainer('annotation')
            )
            ->movePointerToSubElement('entity')
            ->setTagAtPointer(
                $this->query->getTagForData(
                    'annotation',
                    'entity'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('date')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'annotation',
                    ['a_date', 'a_date_descr', 'date_descr_lang']
                )
            )
            ->movePointerToSubElement('dateTime')
            ->setTagAtPointer(
                $this->query->getTagForData(
                    'annotation',
                    'a_date'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'annotation',
                    ['a_date_descr', 'date_descr_lang']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'annotation',
                'a_date_descr',
                'date_descr_lang'
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'annotation',
                    ['description', 'description_language']
                )
            );
        $this->setTagsForLangStringSubElements(
            $structure,
            'annotation',
            'description',
            'description_language'
        );
        return $structure->movePointerToRoot();
    }

    protected function setTagsForClassification(
        ilMDLOMDatabaseStructure $structure
    ): ilMDLOMDatabaseStructure {
        $structure
            ->movePointerToRoot()
            ->movePointerToSubElement('classification')
            ->setTagAtPointer(
                $this->query->getTagForTableContainer('classification')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('purpose')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'classification',
                    ['purpose']
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'classification',
                'purpose'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('taxonPath')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'taxon_path',
                    'meta_classification'
                )->withIsParent(true)
            )
            ->movePointerToSubElement('source')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainerWithParent(
                    'taxon_path',
                    ['source', 'source_language'],
                    'meta_classification',
                    true
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'taxon_path',
                'source',
                'source_language',
                'meta_classification',
                true
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('taxon')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'taxon',
                    'meta_taxon_path'
                )
            )
            ->movePointerToSubElement('id')
            ->setTagAtPointer(
                $this->query->getTagForDataWithParent(
                    'taxon',
                    'taxon_id',
                    'meta_taxon_path'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainerWithParent(
                    'taxon',
                    ['taxon', 'taxon_language'],
                    'meta_taxon_path'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'taxon',
                'taxon',
                'taxon_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainer(
                    'classification',
                    ['description', 'description_language']
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'classification',
                'description',
                'description_language'
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('keyword')
            ->setTagAtPointer(
                $this->query->getTagForTableContainerWithParent(
                    'keyword',
                    'meta_classification'
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'keyword',
                'keyword',
                'keyword_language',
                'meta_classification'
            );
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
                $this->query->getTagForTableContainerWithParent(
                    $table,
                    $parent_type
                )
            )
            ->movePointerToSubElement('catalog')
            ->setTagAtPointer(
                $this->query->getTagForDataWithParent(
                    $table,
                    'catalog',
                    $parent_type
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this->query->getTagForDataWithParent(
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
                $this->query->getTagForTableContainerWithParent(
                    'contribute',
                    $parent_type
                )->withIsParent(true)
            )
            ->movePointerToSubElement('role')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainerWithParent(
                    'contribute',
                    ['role'],
                    $parent_type,
                    true
                )
            );
        $this
            ->setTagsForVocabSubElements(
                $structure,
                'contribute',
                'role',
                $parent_type,
                true
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entity')
            ->setTagAtPointer(
                $this->query->getTagForTableDataWithParent(
                    'entity',
                    'entity',
                    'meta_contribute'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('date')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainerWithParent(
                    'contribute',
                    ['c_date', 'c_date_descr', 'descr_lang'],
                    $parent_type,
                    true
                )
            )
            ->movePointerToSubElement('dateTime')
            ->setTagAtPointer(
                $this->query->getTagForDataWithParent(
                    'contribute',
                    'c_date',
                    $parent_type,
                    true
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->query->getTagForNonTableContainerWithParent(
                    'contribute',
                    ['c_date_descr', 'descr_lang'],
                    $parent_type,
                    true
                )
            );
        $this
            ->setTagsForLangStringSubElements(
                $structure,
                'contribute',
                'c_date_descr',
                'descr_lang',
                $parent_type,
                true
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
        string $parent_type = '',
        bool $second_parent = false
    ): ilMDLOMDatabaseStructure {
        if ($parent_type) {
            $tag_string = $this->query->getTagForDataWithParent(
                $table,
                $field_string,
                $parent_type,
                $second_parent
            );
            $tag_lang = $this->query->getTagForDataWithParent(
                $table,
                $field_lang,
                $parent_type,
                $second_parent
            );
        } else {
            $tag_string = $this->query->getTagForData(
                $table,
                $field_string
            );
            $tag_lang = $this->query->getTagForData(
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
        string $parent_type = '',
        bool $second_parent = false
    ): ilMDLOMDatabaseStructure {
        if ($parent_type) {
            $tag_value = $this->query->getTagForDataWithParent(
                $table,
                $field_value,
                $parent_type,
                $second_parent
            );
        } else {
            $tag_value = $this->query->getTagForData(
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
                $this->factory->database(
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
}
