<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Dashboard\Models\ProfileFieldModel;
use VanillaTests\SiteTestCase;

class ProfileFieldModelTest extends SiteTestCase
{
    /** @var ProfileFieldModel */
    private $profileFieldModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->profileFieldModel = $this->container()->get(ProfileFieldModel::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        \Gdn::sql()->truncate("profileField");
    }

    /**
     * ProfileFieldModel::getValidTypeMapping() just returns mapping information. This test just makes sure
     * the mapping data is an array with keys representing dataType values and values representing arrays of
     * valid formType values.
     *
     * @return void
     */
    public function testGetValidTypeMapping()
    {
        $typeMapping = ProfileFieldModel::getValidTypeMapping();
        $this->assertIsArray($typeMapping);
        foreach ($typeMapping as $dataType => $formTypes) {
            $this->assertContains($dataType, ProfileFieldModel::DATA_TYPES);
            foreach ($formTypes as $formType) {
                $this->assertContains($formType, ProfileFieldModel::FORM_TYPES);
            }
        }
    }

    /**
     * Test ProfileFieldModel::updateSort() can be called with a array map of apiName => sort
     * Only mapped records that match the apiName will be updated.
     *
     * @return void
     * @throws \Exception
     */
    public function testUpdateSorts()
    {
        $this->profileFieldModel->insert($this->profileFieldData("pf1", "pf1"));
        $this->profileFieldModel->insert($this->profileFieldData("pf2", "pf2"));
        $this->profileFieldModel->insert($this->profileFieldData("pf3", "pf3"));
        $this->profileFieldModel->insert($this->profileFieldData("pf4", "pf4"));

        $this->profileFieldModel->updateSorts(["pf2" => 30, "pf3" => 10, "pf4" => 20, "doesnt_exist" => 40]);
        $rows = $this->profileFieldModel->select([], ["orderFields" => "sort", "orderDirection" => "asc"]);
        $this->assertSame(1, $rows[0]["sort"]);
        $this->assertSame(10, $rows[1]["sort"]);
        $this->assertSame(20, $rows[2]["sort"]);
        $this->assertSame(30, $rows[3]["sort"]);
    }

    /**
     * Test that ProfileFieldModel::insert() can be called with a sort value and without a sort value.
     * If no sort value is provided, the value of sort will be 1 + the max sort value.
     *
     * @return void
     * @throws \Exception
     */
    public function testInsert()
    {
        $this->profileFieldModel->insert(["sort" => 5] + $this->profileFieldData("pf1", "pf1"));
        $this->profileFieldModel->insert($this->profileFieldData("pf2", "pf2"));

        $rows = \Gdn::sql()
            ->select()
            ->from("profileField")
            ->orderBy("sort", "asc")
            ->get()
            ->resultArray();
        $this->assertCount(2, $rows);
        $this->assertSame(6, $rows[1]["sort"]);
    }

    /**
     * Provide test data for calls to ProfileFieldModel::insert()
     *
     * @param string $apiName
     * @param string $label
     * @return array
     */
    protected function profileFieldData(string $apiName, string $label): array
    {
        return [
            "apiName" => $apiName,
            "label" => $label,
            "description" => "profile field description",
            "dataType" => ProfileFieldModel::DATA_TYPE_TEXT,
            "formType" => ProfileFieldModel::FORM_TYPE_TEXT,
            "visibility" => "public",
            "mutability" => "all",
            "displayOptions" => ["profiles" => true, "userCards" => true, "posts" => true],
            "required" => true,
        ];
    }
}
