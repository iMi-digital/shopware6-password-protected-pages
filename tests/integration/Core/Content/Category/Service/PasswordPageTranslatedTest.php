<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Test\Controller\StorefrontControllerTestBehaviour;

class PasswordPageTranslatedTest extends TestCase
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

    public function testProtectedPageLoginTranslated(): void
    {
        $response = $this->request('GET', 'default', []);
        static::assertEquals(200, $response->getStatusCode());

        $response = $this->request('GET', 'translated', []);
        static::assertEquals(200, $response->getStatusCode());

        $response = $this->request('POST', 'translated/password-site/login/' . $this->ids->get('protected'), ['password' => 'secret']);
        static::assertEquals(302, $response->getStatusCode());
        static::assertTrue($response->isRedirect());
        static::assertStringContainsString('/navigation/'  . $this->ids->get('protected'), $response->getTargetUrl());

        $response = $this->request('GET', 'translated/protected-navigation/', []);
        static::assertEquals(200, $response->getStatusCode());
        static::assertFalse($response->isRedirect());
    }

    private function createData(): void
    {
        $this->disableDefaultSalesChannel();

        $salesChannel = $this->createSalesChannel();

        $protectedCategory = [
            'id' => $this->ids->create('protected'),
            'name' => 'protected-navigation',
            'type' => 'page',
            'parentId' => $salesChannel['navigationCategoryId'],
            'customFields' => [
                'password_site_password' => 'secret'
            ]
        ];

        $unprotectedCategory = [
            'id' => $this->ids->create('unprotected'),
            'name' => 'unprotected-navigation',
            'type' => 'page',
            'parentId' => $salesChannel['navigationCategoryId'],
        ];

        $this->getContainer()->get('category.repository')->create([$protectedCategory, $unprotectedCategory], Context::createDefaultContext());
    }

    private function createSalesChannel(): array
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $paymentMethod = $this->getAvailablePaymentMethod();

        $languages = $this->getLanguages();

        $salesChannel = [
            'id' => $this->ids->create('saleschannel'),
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'name' => 'Test case sales channel',
            'active' => true,
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            'languageId' => $languages['English']->getId(),
            'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $paymentMethod->getId(),
            'paymentMethods' => [['id' => $paymentMethod->getId()]],
            'shippingMethodId' => $this->getAvailableShippingMethod()->getId(),
            'navigationCategoryId' => $this->getValidCategoryId(),
            'countryId' => $this->getValidCountryId(null),
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => [['id' => $languages['English']->getId()], ['id' => $languages['Deutsch']->getId()]],
            'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'domains' => [
                [
                    'languageId' => $languages['English']->getId(),
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => EnvironmentHelper::getVariable('APP_URL') . '/default',
                ],
                [
                    'languageId' => $languages['Deutsch']->getId(),
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => EnvironmentHelper::getVariable('APP_URL') . '/translated',
                ],
            ],
            'countries' => [['id' => $this->getValidCountryId(null)]],
        ];

        $salesChannelRepository->upsert([$salesChannel], Context::createDefaultContext());

        return $salesChannel;
    }

    private function disableDefaultSalesChannel()
    {

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));

        /** @var SalesChannelEntity $defaultSalesChannel */
        $defaultSalesChannel = $this->getContainer()->get('sales_channel.repository')
            ->search($criteria, Context::createDefaultContext())->first();

        $this->getContainer()->get('sales_channel.repository')->delete([['id' => $defaultSalesChannel->getId()]], Context::createDefaultContext());
    }

    private function getLanguages()
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', ['English', 'Deutsch']));

        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');

        $resultingLanguages = $languageRepository->search($criteria, Context::createDefaultContext())->getElements();

        $languages = [];

        foreach ($resultingLanguages as $language) {
            $languages[$language->getName()] = $language;
        }

        return $languages;
    }

}
