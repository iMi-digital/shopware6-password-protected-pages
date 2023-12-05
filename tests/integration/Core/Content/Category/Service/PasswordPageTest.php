<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

class PasswordPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    protected SalesChannelContext $context;
    private string $categoryId;
    private TestDataCollection $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ids = new TestDataCollection();
        $this->createData();
    }

    public function testPasswordPageTrigger(): void
    {
        $response = $this->request('GET', '/my-navigation/', []);
        static::assertEquals(302, $response->getStatusCode());
        static::assertTrue($response->isRedirect());

    }

    private function createData(): void
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->getContainer()->get('sales_channel.repository')->search(
            (new Criteria())->addFilter(
                new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT),
            ),
            Context::createDefaultContext()
        )->first();

        $this->categoryId = $this->ids->create('category');

        $category = [
            'id' => $this->categoryId,
            'name' => 'my-navigation',
            'type' => 'page',
            'parentId' => $salesChannel->getNavigationCategoryId(),
            'customFields' => [
                'password_site_password' => 'password'
            ]
        ];

        $this->getContainer()->get('category.repository')->create([$category], Context::createDefaultContext());
    }
}
