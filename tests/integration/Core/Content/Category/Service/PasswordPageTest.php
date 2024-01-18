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
        static::assertStringContainsString('/password-site/login/', $response->getTargetUrl());
    }

    public function testProtectedPageLogin(): void
    {
        $response = $this->request('POST', '/password-site/login/' . $this->ids->get('protected'), ['password' => 'secret']);
        static::assertEquals(302, $response->getStatusCode());
        static::assertTrue($response->isRedirect());
        static::assertStringContainsString('/navigation/'  . $this->ids->get('protected'), $response->getTargetUrl());

        $response = $this->request('GET', '/protected-navigation/', []);
        static::assertEquals(200, $response->getStatusCode());
        static::assertFalse($response->isRedirect());
    }

    public function testProtectedPageLoginFailed(): void
    {
        $response = $this->request('POST', '/password-site/login/' . $this->ids->get('protected'), ['password' => 'not_my_secret']);
        static::assertEquals(302, $response->getStatusCode());
        static::assertTrue($response->isRedirect());
        static::assertStringContainsString('/password-site/login/', $response->getTargetUrl());

        $response = $this->request('GET', '/protected-navigation/', []);
        static::assertEquals(302, $response->getStatusCode());
        static::assertTrue($response->isRedirect());
        static::assertStringContainsString('/password-site/login/', $response->getTargetUrl());
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
                'password_site_password' => 'secret'
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
