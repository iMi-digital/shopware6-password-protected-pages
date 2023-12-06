<?php declare(strict_types=1);

use ImiDiPasswordSite\Service\PasswordPathService;
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
    private TestDataCollection $ids;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ids = new TestDataCollection();
        $this->createData();
    }

    public function testProtectedPageTrigger(): void
    {
        $response = $this->request('GET', '/protected-navigation/', []);
        static::assertEquals(302, $response->getStatusCode());
        static::assertTrue($response->isRedirect());
        self::assertStringContainsString('/restricted/', $response->getTargetUrl());
    }

    public function testProtectedPageLogin(): void
    {
        $this->getSession()->set(PasswordPathService::AUTH_SESSION_PREFIX . $this->ids->get('protected'), true);
        $response = $this->request('GET', '/protected-navigation/', []);
        static::assertEquals(200, $response->getStatusCode());
        static::assertFalse($response->isRedirect());
    }

    public function testUnprotectedPage(): void
    {
        $response = $this->request('GET', '/unprotected-navigation/', []);
        static::assertEquals(200, $response->getStatusCode());
        static::assertFalse($response->isRedirect());
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

        $protectedCategory = [
            'id' => $this->ids->create('protected'),
            'name' => 'protected-navigation',
            'type' => 'page',
            'parentId' => $salesChannel->getNavigationCategoryId(),
            'customFields' => [
                'password_site_password' => 'password'
            ]
        ];

        $unprotectedCategory = [
            'id' => $this->ids->create('unprotected'),
            'name' => 'unprotected-navigation',
            'type' => 'page',
            'parentId' => $salesChannel->getNavigationCategoryId(),
        ];

        $this->getContainer()->get('category.repository')->create([$protectedCategory, $unprotectedCategory], Context::createDefaultContext());
    }
}
