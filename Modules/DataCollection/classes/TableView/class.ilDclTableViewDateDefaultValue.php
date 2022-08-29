<?php

/**
 * Class ilDclTableViewDateDefaultValue
 * @author  Jannik Dolf <jd@studer-raimann.ch>
 */
class ilDclTableViewDateDefaultValue extends ilDclTableViewBaseDefaultValue
{
    /**
     * @var int
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        integer
     * @db_length           4
     * @db_sequence         true
     */
    protected ?int $id;
    /**
     * @var int
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        integer
     * @db_length           4
     */
    protected int $tview_set_id;
    /**
     * @var string
     * @db_has_field        true
     * @db_fieldtype        timestamp
     * @db_is_notnull       true
     */
    protected string $value;

    /**
     * @return string
     * @description Return the Name of your Database Table
     */
    public static function returnDbTableName(): string
    {
        return "il_dcl_stloc3_default";
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTviewSetId(): int
    {
        return $this->tview_set_id;
    }

    public function setTviewSetId(int $tview_set_id): void
    {
        $this->tview_set_id = $tview_set_id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function createDefaultValueRecord(): void
    {
    }
}
