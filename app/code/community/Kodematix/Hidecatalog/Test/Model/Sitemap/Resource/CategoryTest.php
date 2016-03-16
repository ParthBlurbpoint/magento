<?php


class Kodematix_Hidecatalog_Test_Model_Sitemap_Resource_CategoryTest
    extends EcomDev_PHPUnit_Test_Case
{
    protected $class = 'Kodematix_Hidecatalog_Model_Sitemap_Resource_Catalog_Category';

    /**
     * @return Kodematix_Hidecatalog_Model_Sitemap_Resource_Catalog_Category
     */
    private function getInstance()
    {
        /** @var Kodematix_Hidecatalog_Model_Sitemap_Resource_Catalog_Category $instance */
        $instance = new $this->class;
        return $instance;
    }

    private function getMockBehavior()
    {
        $mockBehavior = $this->getMockBuilder('Kodematix_Hidecatalog_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapCategory')
            ->getMock();

        return $mockBehavior;
    }

    public function testItIsRewritten()
    {
        $result = Mage::getConfig()->getResourceModelClassName('sitemap/catalog_category');
        $this->assertEquals($this->class, $result);
    }

    public function testItExists()
    {
        $this->assertTrue(class_exists($this->class, true), "Class {$this->class} does not exist or can't be found by the autoloader");
    }

    public function testItExtendsTheOriginalClass()
    {
        $instance = $this->getInstance();
        $this->assertInstanceOf($this->class, $instance);
        $this->assertInstanceOf('Mage_Sitemap_Model_Resource_Catalog_Category', $instance);
    }

    public function testItDelegatesToFilterBehavior()
    {
        $instance = $this->getInstance();

        $storeId = $this->app()->getDefaultStoreView()->getId();

        $mockBehavor = $this->getMockBehavior();
        $mockBehavor->expects($this->once())
            ->method('setStoreId')
            ->with($storeId);
        $mockBehavor->expects($this->once())
            ->method('addNotLoggedInGroupFilter')
            ->with($this->isInstanceOf('Varien_Db_Select'));
        $instance->setAddFilterBehavior($mockBehavor);

        $result = $instance->getCollection($storeId);

        $this->assertTrue(false !== $result, "Expected getCollection to return an array, received bool false");
    }
} 