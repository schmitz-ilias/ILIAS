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
    protected ilDBInterface $db;

    protected ilMDLOMDatabaseStructure $structure;

    public function __construct(
        ilMDTagFactory $factory,
        ilDBInterface $db
    ) {
        $this->factory = $factory;
        $this->db = $db;
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
                )->withIsParent(true)
            )
            ->movePointerToSubElement('orComposite')
            ->setTagAtPointer(
                $this->getTagForOrComposite()
            )
            ->movePointerToSubElement('type')
            ->setTagAtPointer(
                $this->factory->databaseTag(
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
                $this->factory->databaseTag(
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
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('name')
            ->setTagAtPointer(
                $this->getTagForOrCompositeName()
            )
            ->movePointerToSubElement('value')
            ->setTagAtPointer(
                $this->getTagForOrCompositeData(
                    'operating_system_name',
                    'browser_name'
                )
            )
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
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('minimumVersion')
            ->setTagAtPointer(
                $this->getTagForOrCompositeData(
                    'os_min_version',
                    'browser_minimum_version'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('maximumVersion')
            ->setTagAtPointer(
                $this->getTagForOrCompositeData(
                    'os_max_version',
                    'browser_maximum_version'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSuperElement()
            ->movePointerToSubElement('installationRemarks')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
                    'technical',
                    ['duration', 'duration_descr', 'duration_descr_lang']
                )
            )
            ->movePointerToSubElement('duration')
            ->setTagAtPointer(
                $this->getTagForData(
                    'technical',
                    'duration'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainer('educational')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('interactivityType')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainerWithParent(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
                    'educational',
                    ['typical_learning_time', 'tlt_descr', 'tlt_descr_lang']
                )
            )
            ->movePointerToSubElement('duration')
            ->setTagAtPointer(
                $this->getTagForData(
                    'educational',
                    'typical_learning_time'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainerWithParent(
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
                $this->getTagForTableDataWithParent(
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
                $this->getTagForTableContainer('rights')
            )
            ->movePointerToSubElement('cost')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainer('relation')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('kind')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainerWithParentAcrossTwoTables(
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
                $this->getTagForTableContainerWithParent(
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
                $this->getTagForTableContainer('annotation')
            )
            ->movePointerToSubElement('entity')
            ->setTagAtPointer(
                $this->getTagForData(
                    'annotation',
                    'entity'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('date')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
                    'annotation',
                    ['a_date', 'a_date_descr', 'date_descr_lang']
                )
            )
            ->movePointerToSubElement('dateTime')
            ->setTagAtPointer(
                $this->getTagForData(
                    'annotation',
                    'a_date'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainer('classification')
                     ->withIsParent(true)
            )
            ->movePointerToSubElement('purpose')
            ->setTagAtPointer(
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainerWithParent(
                    'taxon_path',
                    'meta_classification'
                )->withIsParent(true)
            )
            ->movePointerToSubElement('source')
            ->setTagAtPointer(
                $this->getTagForNonTableContainerWithParent(
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
                $this->getTagForTableContainerWithParent(
                    'taxon',
                    'meta_taxon_path'
                )
            )
            ->movePointerToSubElement('id')
            ->setTagAtPointer(
                $this->getTagForDataWithParent(
                    'taxon',
                    'taxon_id',
                    'meta_taxon_path'
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('entry')
            ->setTagAtPointer(
                $this->getTagForNonTableContainerWithParent(
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
                $this->getTagForNonTableContainer(
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
                $this->getTagForTableContainerWithParent(
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
                    $parent_type,
                    true
                )
            )
            ->movePointerToSubElement('dateTime')
            ->setTagAtPointer(
                $this->getTagForDataWithParent(
                    'contribute',
                    'c_date',
                    $parent_type,
                    true
                )
            )
            ->movePointerToSuperElement()
            ->movePointerToSubElement('description')
            ->setTagAtPointer(
                $this->getTagForNonTableContainerWithParent(
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
            $tag_string = $this->getTagForDataWithParent(
                $table,
                $field_string,
                $parent_type,
                $second_parent
            );
            $tag_lang = $this->getTagForDataWithParent(
                $table,
                $field_lang,
                $parent_type,
                $second_parent
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
        string $parent_type = '',
        bool $second_parent = false
    ): ilMDLOMDatabaseStructure {
        if ($parent_type) {
            $tag_value = $this->getTagForDataWithParent(
                $table,
                $field_value,
                $parent_type,
                $second_parent
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
        string $parent_type,
        bool $second_parent = false
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
            [
                self::EXP_MD_ID,
                $second_parent ?
                    self::EXP_SECOND_PARENT_MD_ID :
                    self::EXP_PARENT_MD_ID
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
        $read_fields = '(';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db->quoteIdentifier($field) .
                ') > 0 OR ';
        }
        $read_fields = substr($read_fields, 0, -3) . ') AND ';
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $read_fields .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s AND' .
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
            [self::EXP_MD_ID, self::EXP_SUPER_MD_ID]
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
    protected function getTagForNonTableContainerWithParent(
        string $table,
        array $fields,
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($table);
        if (empty($fields)) {
            throw new ilMDDatabaseException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '(';
        foreach ($fields as $field) {
            $read_fields .= 'CHAR_LENGTH(' . $this->db->quoteIdentifier($field) .
                ') > 0 OR ';
        }
        $read_fields = substr($read_fields, 0, -3) . ') AND ';
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' WHERE ' . $read_fields . ' parent_type = ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) . ' AND ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) . ' = %s' .
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
            [
                self::EXP_MD_ID,
                self::EXP_SUPER_MD_ID,
                $second_parent ?
                    self::EXP_SECOND_PARENT_MD_ID :
                    self::EXP_PARENT_MD_ID
            ]
        );
    }

    /**
     * Returns the appropriate database tag for the technical: orComposite
     * container element, which is a special case.
     */
    protected function getTagForOrComposite(): ilMDDatabaseTag
    {
        $read =
            'SELECT ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            " FROM ((SELECT '" . self::MD_ID_OS . "'" .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type, ' .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' WHERE (CHAR_LENGTH(operating_system_name) > 0 OR' .
            ' CHAR_LENGTH(os_min_version) > 0 OR CHAR_LENGTH(os_max_version) > 0)' .
            ') UNION (' .
            "SELECT '" . self::MD_ID_BROWSER . "'" .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type, ' .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' WHERE (CHAR_LENGTH(browser_name) > 0 OR' .
            ' CHAR_LENGTH(browser_minimum_version) > 0 OR CHAR_LENGTH(browser_maximum_version) > 0)))' .
            " AS u WHERE u.parent_type = 'meta_technical' AND u." .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND u.parent_id = %s AND u.rbac_id = %s AND u.obj_id = %s AND u.obj_type = %s' .
            ' ORDER BY u.' . $this->db->quoteIdentifier(self::ID_NAME['requirement']);
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' SET operating_system_name = CASE %s WHEN ' . self::MD_ID_OS . " THEN ''" .
            ' ELSE operating_system_name END, ' .
            ' os_min_version = CASE %s WHEN ' . self::MD_ID_OS . " THEN ''" .
            ' ELSE os_min_version END, ' .
            ' os_max_version = CASE %s WHEN ' . self::MD_ID_OS . " THEN ''" .
            ' ELSE os_max_version END, ' .
            ' browser_name = CASE %s WHEN ' . self::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_name END, ' .
            ' browser_minimum_version = CASE %s WHEN ' . self::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_minimum_version END, ' .
            ' browser_maximum_version = CASE %s WHEN ' . self::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_maximum_version END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            '',
            $read,
            '',
            $delete,
            self::TABLES['requirement'],
            [
                self::EXP_MD_ID,
                self::EXP_MD_ID,
                self::EXP_MD_ID,
                self::EXP_MD_ID,
                self::EXP_MD_ID,
                self::EXP_MD_ID,
                self::EXP_SUPER_MD_ID,
                self::EXP_SECOND_PARENT_MD_ID
            ]
        );
    }

    /**
     * Returns the appropriate database tag for the technical: orComposite:
     * name container element, which is a special case.
     */
    protected function getTagForOrCompositeName(): ilMDDatabaseTag
    {
        $read =
            "SELECT '%s' AS " . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' WHERE CASE %s WHEN ' . self::MD_ID_OS . ' THEN CHAR_LENGTH(operating_system_name)' .
            ' WHEN ' . self::MD_ID_BROWSER . ' THEN CHAR_LENGTH(browser_name) END > 0 ' .
            " AND parent_type = 'meta_technical' AND " .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME['requirement']);
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' SET operating_system_name = CASE %s WHEN ' . self::MD_ID_OS . " THEN ''" .
            ' ELSE operating_system_name END, ' .
            ' browser_name = CASE %s WHEN ' . self::MD_ID_BROWSER . " THEN ''" .
            ' ELSE browser_name END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            '',
            $read,
            '',
            $delete,
            self::TABLES['requirement'],
            [
                self::EXP_SUPER_MD_ID,
                self::EXP_SUPER_MD_ID,
                self::EXP_PARENT_MD_ID,
                self::EXP_SECOND_PARENT_MD_ID
            ]
        );
    }

    /**
     * Returns the appropriate database tag for data-carrying sub-elements
     * of technical: orComposite element, which are special cases.
     */
    protected function getTagForOrCompositeData(
        string $field_os,
        string $field_browser
    ): ilMDDatabaseTag {
        $read =
            "SELECT '%s' AS " . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ', CASE %s WHEN ' . self::MD_ID_OS . ' THEN ' . $this->db->quoteIdentifier($field_os) .
            ' WHEN ' . self::MD_ID_BROWSER . ' THEN  ' . $this->db->quoteIdentifier($field_browser) .
            ' END AS ' . $this->db->quoteIdentifier(self::RES_DATA) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s' .
            ' ORDER BY ' . $this->db->quoteIdentifier(self::ID_NAME['requirement']);
        $create_and_update =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' SET ' . $this->db->quoteIdentifier($field_os) . ' = CASE %s WHEN ' .
            self::MD_ID_OS . ' THEN %s' .
            ' ELSE ' . $this->db->quoteIdentifier($field_os) . ' END, ' .
            $this->db->quoteIdentifier($field_browser) . ' = CASE %s WHEN ' .
            self::MD_ID_BROWSER . ' THEN %s' .
            ' ELSE ' . $this->db->quoteIdentifier($field_browser) . ' END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES['requirement']) .
            ' SET ' . $this->db->quoteIdentifier($field_os) . ' = CASE %s WHEN ' .
            self::MD_ID_OS . " THEN ''" .
            ' ELSE ' . $this->db->quoteIdentifier($field_os) . ' END, ' .
            $this->db->quoteIdentifier($field_browser) . ' = CASE %s WHEN ' .
            self::MD_ID_BROWSER . " THEN ''" .
            ' ELSE ' . $this->db->quoteIdentifier($field_browser) . ' END' .
            " WHERE parent_type = 'meta_technical' AND " .
            $this->db->quoteIdentifier(self::ID_NAME['requirement']) . ' = %s' .
            ' AND parent_id = %s AND rbac_id = %s AND obj_id = %s AND obj_type = %s';

        return $this->factory->databaseTag(
            $create_and_update,
            $read,
            $create_and_update,
            $delete,
            self::TABLES['requirement'],
            [
                self::EXP_SUPER_MD_ID,
                self::EXP_DATA,
                self::EXP_SUPER_MD_ID,
                self::EXP_DATA,
                self::EXP_PARENT_MD_ID,
                self::EXP_SECOND_PARENT_MD_ID
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
    protected function getTagForNonTableContainerWithParentAcrossTwoTables(
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
            throw new ilMDDatabaseException(
                'A container element can not be empty.'
            );
        }
        $read_fields = '(';
        foreach ($first_fields as $field) {
            $read_fields .= 'CHAR_LENGTH(t1.' . $this->db->quoteIdentifier($field) .
                ') > 0 OR ';
        }
        foreach ($second_fields as $field) {
            $read_fields .= 'CHAR_LENGTH(t2.' . $this->db->quoteIdentifier($field) .
                ') > 0 OR ';
        }
        $read_fields = substr($read_fields, 0, -3) . ') AND ';
        $read =
            'SELECT t1.parent_id' .
            ' AS ' . $this->db->quoteIdentifier(self::RES_MD_ID) .
            ' FROM ' . $this->db->quoteIdentifier(self::TABLES[$first_table]) .
            ' AS t1,' . $this->db->quoteIdentifier(self::TABLES[$second_table]) .
            ' AS t2 WHERE ' . $read_fields . ' t1.parent_type = ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND t1.parent_type = t2.parent_type' .
            ' AND t1.parent_id = t2.parent_id AND t1.parent_id = %s' .
            ' AND t1.rbac_id = %s AND t1.obj_id = %s AND t1.obj_type = %s' .
            ' ORDER BY t1.parent_type';
        $delete_fields = '';
        foreach ($first_fields as $field) {
            $delete_fields .= 't1.' . $this->db->quoteIdentifier($field) . " = '', ";
        }
        foreach ($first_fields as $field) {
            $delete_fields .= 't2.' . $this->db->quoteIdentifier($field) . " = '', ";
        }
        $delete_fields = substr($delete_fields, 0, -2) . ' ';
        $delete =
            'UPDATE ' . $this->db->quoteIdentifier(self::TABLES[$first_table]) .
            ' AS t1,' . $this->db->quoteIdentifier(self::TABLES[$second_table]) .
            ' AS t2 SET ' . $delete_fields .
            'WHERE t1.parent_type = ' .
            $this->db->quote($parent_type, ilDBConstants::T_TEXT) .
            ' AND t1.parent_type = t2.parent_type' .
            ' AND t1.parent_id = t2.parent_id AND t1.parent_id = %s' .
            ' AND t1.rbac_id = %s AND t1.obj_id = %s AND t1.obj_type = %s';

        return $this->factory->databaseTag(
            '',
            $read,
            '',
            $delete,
            '',
            [
                $second_parent ?
                    self::EXP_SECOND_PARENT_MD_ID :
                    self::EXP_PARENT_MD_ID
            ]
        );
    }

    /**
     * Returns the appropriate database tag for a data element
     * without its own table, but where a parent has to be given.
     */
    protected function getTagForDataWithParent(
        string $table,
        string $field,
        string $parent_type,
        bool $second_parent = false
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
            [
                self::EXP_DATA,
                self::EXP_SUPER_MD_ID,
                $second_parent ?
                    self::EXP_SECOND_PARENT_MD_ID :
                    self::EXP_PARENT_MD_ID
            ]
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
        string $parent_type,
        bool $second_parent = false
    ): ilMDDatabaseTag {
        $this->checkTable($table);

        $create =
            'INSERT INTO ' . $this->db->quoteIdentifier(self::TABLES[$table]) .
            ' (' . $this->db->quoteIdentifier($field) . ', ' .
            $this->db->quoteIdentifier(self::ID_NAME[$table]) .
            ', parent_type, parent_id, rbac_id, obj_id, obj_type) VALUES (%s, %s, ' .
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
            [
                self::EXP_DATA,
                self::EXP_MD_ID,
                $second_parent ?
                    self::EXP_SECOND_PARENT_MD_ID :
                    self::EXP_PARENT_MD_ID
            ]
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
